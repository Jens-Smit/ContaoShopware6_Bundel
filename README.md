# Shopware Bundle für Contao

Ein Contao Frontend-Bundle zur Integration von Shopware-Produkten in Contao-Websites.

## Übersicht

Dieses Bundle ermöglicht es, Shopware-Produkte über die Store-API in Contao-Frontend-Module zu integrieren. Es stellt ein konfigurierbares Modul zur Verfügung, das Produktdaten von einer Shopware-Installation abruft und diese in einer anpassbaren Vorlage darstellt.

## Features

- ✅ Integration von Shopware Store-API
- ✅ Automatischer Produktabruf mit Bildern und Preisen
- ✅ Anpassbare Twig-Templates
- ✅ Umfassendes Error-Handling und Logging
- ✅ Dependency Injection für HTTP-Client und Logger
- ✅ Containerisierte Entwicklungsumgebung (DDEV-kompatibel)

## Systemanforderungen

- **PHP:** ^8.1
- **Contao:** ^4.13 oder ^5.0
- **Shopware:** 6.x mit aktivierter Store-API
- **Composer:** für Paketmanagement

## Installation

### 1. Bundle installieren

```bash
composer require shopware_bundle/shopware-bundle
```

### 2. Bundle aktivieren

Das Bundle wird automatisch über den Contao Manager aktiviert. Die Konfiguration erfolgt über:

```php
// src/ContaoManager/Plugin.php
class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ShopwareBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
```

### 3. Services konfigurieren

Die Services werden automatisch über `services.yaml` konfiguriert:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    guzzle.client:
        class: GuzzleHttp\Client
        public: true

    shopware_bundle\ShopwareBundle\Module\ShopwareModule:
        public: true
        calls:
            - method: setHttpClient
              arguments: ['@guzzle.client']
            - method: setLogger
              arguments: ['@monolog.logger.contao']
```

## Konfiguration

### Shopware-Verbindung einrichten

Bearbeiten Sie die Datei `src/Module/ShopwareModule.php` und passen Sie folgende Parameter an:

```php
// Container-Host IP (für DDEV: Container zu Container Kommunikation)
$containerHostIp = '172.20.0.1'; 

// Port der Shopware-Installation
$shopwareHostPort = '8090'; 

// Shopware Access Key (aus der Administration)
$shopwareAccessKey = 'DEINER-SHOPWARE-ACCESS-KEY';
```

### Shopware Access Key generieren

1. Melden Sie sich in der Shopware-Administration an
2. Navigieren Sie zu **Einstellungen** → **System** → **Integrationen**
3. Erstellen Sie eine neue Integration oder verwenden Sie eine bestehende
4. Kopieren Sie den **Access Key** und tragen Sie ihn in der Konfiguration ein

## Verwendung

### Frontend-Modul hinzufügen

1. Melden Sie sich im Contao-Backend an
2. Gehen Sie zu **Layout** → **Frontend-Module**
3. Erstellen Sie ein neues Modul vom Typ **"Shopware" → "produktList"**
4. Konfigurieren Sie das Modul nach Ihren Wünschen
5. Fügen Sie das Modul zu einer Seite oder einem Artikel hinzu

### Template anpassen

Das Standard-Template befindet sich unter:
```
src/Resources/contao/templates/modules/mod_produktList.html5
```

Sie können dieses Template in Ihrem Theme-Ordner überschreiben:
```
templates/modules/mod_produktList.html5
```

### Verfügbare Template-Variablen

```php
$this->products    // Array mit Produktdaten
$this->message     // Status- oder Fehlermeldung
```

### Produktdaten-Struktur

```php
[
    'name' => 'Produktname',
    'description' => 'Produktbeschreibung',
    'price' => '19,99',  // Formatierter Preis
    'imageUrl' => 'https://shop.example.com/media/image.jpg'
]
```

## Entwicklung

### Projektstruktur

```
shopware_bundle/
├── composer.json                           # Composer-Konfiguration
├── src/
│   ├── ContaoManager/
│   │   └── Plugin.php                      # Contao Manager Plugin
│   ├── DependencyInjection/
│   │   └── ShopwareBundleExtension.php     # Service Container Extension
│   ├── Module/
│   │   └── ShopwareModule.php              # Haupt-Modul-Klasse
│   ├── Resources/
│   │   ├── config/
│   │   │   ├── config.yaml                 # Bundle-Konfiguration
│   │   │   └── services.yaml               # Service-Definitionen
│   │   ├── contao/
│   │   │   ├── config/
│   │   │   │   └── config.php              # Contao-Modul-Registration
│   │   │   └── templates/
│   │   │       └── modules/
│   │   │           └── mod_produktList.html5  # Frontend-Template
│   │   └── public/
│   │       ├── scripts.js                  # JavaScript (leer)
│   │       └── styles.css                  # CSS (leer)
│   └── ShopwareBundle.php                  # Bundle-Hauptklasse
```

### DDEV-Entwicklung

Für die Entwicklung mit DDEV sind folgende Container-IPs relevant:

- **Host zu Container:** `127.0.0.1:8090` (Browser-Zugriff)
- **Container zu Container:** `172.20.0.1:8090` (API-Calls)

Die IP-Adresse können Sie mit folgendem Befehl ermitteln:

```bash
ddev ssh
ip route show default | awk '{print $3}'
```

### Error Handling

Das Bundle implementiert umfassendes Error Handling für:

- **HTTP-Fehler:** ClientException, ServerException, RequestException
- **JSON-Parsing-Fehler:** JsonException
- **Allgemeine Fehler:** Throwable

Alle Fehler werden geloggt und benutzerfreundliche Meldungen angezeigt.

### Testing

Für Unit-Tests ist PHPUnit vorkonfiguriert:

```bash
composer require --dev phpunit/phpunit
vendor/bin/phpunit
```

## API-Referenz

### ShopwareModule

```php
class ShopwareModule extends Module
{
    // HTTP-Client setzen (Dependency Injection)
    public function setHttpClient(Client $client): void
    
    // Logger setzen (Dependency Injection)  
    public function setLogger(LoggerInterface $logger): void
    
    // Hauptverarbeitungslogik
    protected function compile(): void
}
```

### Shopware Store-API Endpoint

```
POST /store-api/product
```

**Headers:**
```
Content-Type: application/json
Accept: application/json
sw-access-key: YOUR_ACCESS_KEY
```

**Request Body:**
```json
{
    "associations": {
        "cover": {
            "associations": {
                "media": []
            }
        }
    },
    "limit": 5
}
```

## Troubleshooting

### Häufige Probleme

**1. "Connection refused" Fehler**
- Überprüfen Sie die Container-IP-Adresse
- Stellen Sie sicher, dass Shopware läuft
- Kontrollieren Sie den Port

**2. "Access denied" Fehler**
- Überprüfen Sie den Access Key
- Stellen Sie sicher, dass die Integration in Shopware aktiviert ist

**3. Bilder werden nicht angezeigt**
- Kontrollieren Sie die URL-Ersetzungslogik
- Überprüfen Sie die Shopware-Media-Konfiguration

**4. Keine Produkte angezeigt**
- Überprüfen Sie, ob Produkte in Shopware vorhanden sind
- Kontrollieren Sie die API-Response-Struktur

### Debug-Tipps

1. **Logging aktivieren:**
   ```php
   $this->logger->debug('API Response', ['data' => $data]);
   ```

2. **Response-Struktur prüfen:**
   ```php
   var_dump($data); // Temporär für Debugging
   ```

3. **Netzwerk-Verbindung testen:**
   ```bash
   curl -X POST http://172.20.0.1:8090/store-api/product \
        -H "Content-Type: application/json" \
        -H "sw-access-key: YOUR_KEY"
   ```

## Erweiterungen

### Custom Templates

Erstellen Sie eigene Templates in:
```
templates/modules/mod_produktList_custom.html5
```

Und setzen Sie den Template-Namen:
```php
protected $strTemplate = 'mod_produktList_custom';
```

### Zusätzliche API-Parameter

Erweitern Sie die API-Anfrage in der `compile()`-Methode:

```php
'json' => [
    'associations' => [
        'cover' => ['associations' => ['media' => []]],
        'categories' => [],
        'manufacturer' => []
    ],
    'limit' => 10,
    'filter' => [
        ['type' => 'equals', 'field' => 'active', 'value' => true]
    ]
]
```

### CSS-Anpassungen

Fügen Sie eigene Styles in `src/Resources/public/styles.css` hinzu:

```css
.mod_shopware_product_list {
    /* Ihre Styles */
}
```

## Lizenz

Dieses Bundle ist unter der LGPL-3.0-or-later Lizenz veröffentlicht.

## Autor

**Jens Smit**  
Homepage: [https://www.jenssmit.de](https://www.jenssmit.de)

## Support

Bei Fragen oder Problemen erstellen Sie bitte ein Issue im Repository oder kontaktieren Sie den Autor über die angegebene Homepage.

---

*Powered by Jens Smit*