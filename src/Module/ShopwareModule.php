<?php

namespace shopware_bundle\ShopwareBundle\Module;

use Contao\Module;
use Contao\ModuleModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ShopwareModule extends Module
{
    protected $strTemplate = 'mod_produktList';

    private Client $httpClient;
    private LoggerInterface $logger;

    public function __construct(ModuleModel $objModule, string $strColumn = 'main', ?Client $httpClient = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($objModule, $strColumn);
        $this->httpClient = $httpClient ?? new Client();
        $this->logger = $logger ?? new NullLogger();
    }

    public function setHttpClient(Client $client): void
    {
        $this->httpClient = $client;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function compile(): void
    {
        // IP-Adresse des Hosts, die Sie mit 'ddev ssh' und 'ip route show default | awk '{print $3}'' gefunden haben
        // Diese IP ist FÜR DIE KOMMUNIKATION VOM CONTAO-CONTAINER ZU SHOPWARE.
        $containerHostIp = '172.20.0.1'; 
        // Dies ist der PORT, unter dem Shopware von Ihrem HOST-SYSTEM (Browser) aus erreichbar ist.
        $shopwareHostPort = '8090'; 
        // Die API-URL für den Guzzle-Request (Contao-Container -> Shopware-Container)
        $apiUrl = 'http://' . $containerHostIp . ':' . $shopwareHostPort . '/store-api/product';
        $shopwareAccessKey = 'DEINER-SHOPWARE-ACCESS-KEY'; // Ersetzen Sie dies durch Ihren tatsächlichen Access Key

        $products = [];
        $message = '';

        try {
            $response = $this->httpClient->request('POST', $apiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'sw-access-key' => $shopwareAccessKey,
                ],
                'json' => [
                    'associations' => [
                        'cover' => ['associations' => ['media' => []]]
                    ],
                    'limit' => 5 // Optional: Begrenzen Sie die Anzahl der Produkte
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($data['elements']) && is_array($data['elements'])) {
                foreach ($data['elements'] as $productData) {
                    $product = [];
                    $product['name'] = $productData['translated']['name'] ?? $productData['name'] ?? 'Unbekanntes Produkt';
                    $product['description'] = $productData['translated']['description'] ?? $productData['description'] ?? '';

                    if (isset($productData['calculatedPrice']['unitPrice'])) {
                        $product['price'] = number_format($productData['calculatedPrice']['unitPrice'], 2, ',', '.');
                    } else {
                        $product['price'] = 'N/A';
                    }
                    
                    // *** KORRIGIERTE ANPASSUNG HIER FÜR DIE BILD-URL ***
                    // Shopware liefert URLs mit 127.0.0.1 (seine eigene interne Sicht).
                    // Ihr Browser muss aber die URL verwenden, die für ihn erreichbar ist,
                    // also auch 127.0.0.1:8090 auf Ihrem Host-System.

                    if (isset($productData['cover']['media']['url'])) {
                        $originalImageUrl = $productData['cover']['media']['url'];
                        
                      // DIESEN String ersetzen wir, falls er im Original vorhanden ist.
                        $product['imageUrl'] = str_replace(
                        $containerHostIp,"127.0.0.1",$originalImageUrl);

                        
                         // Nimm die URL, wie Shopware sie liefert
                    } else {
                        $product['imageUrl'] = null;
                    }

                    $products[] = $product;
                }
                $message = 'Erfolgreich ' . count($products) . ' Produkte geladen.';
            } else {
                $message = 'Keine Produkte gefunden oder unerwartetes Datenformat.';
                $this->logger->error('Shopware API: Invalid response structure.', ['data' => $data]);
            }

        } catch (ClientException | ServerException | RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Keine Antwort.';
            $message = 'Fehler beim API-Abruf: ' . $e->getMessage();
            $this->logger->error('Shopware API HTTP Fehler', [
                'message' => $e->getMessage(),
                'response' => $responseBody
            ]);

        } catch (\JsonException $e) {
            $message = 'Fehler beim Verarbeiten der API-Antwort (ungültiges JSON): ' . $e->getMessage();
            $this->logger->error('Shopware API JSON Fehler', ['message' => $e->getMessage()]);

        } catch (\Throwable $e) {
            $message = 'Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage();
            $this->logger->error('Unerwarteter Fehler in ShopwareModule', ['message' => $e->getMessage()]);
        }

        $this->Template->products = $products;
        $this->Template->message = $message;
    }
}