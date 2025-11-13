# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [1.0.0] - 2025-01-XX

### Hinzugefügt
- Initiales Release des PeerTube Video Manager Plugins
- 4 Hauptshortcodes:
  - `[pt-last-videos]` - Zeigt neueste Videos der Instanz
  - `[pt-latest-per-channel]` - Zeigt letztes Video pro Kanal
  - `[pt-channel-videos]` - Zeigt Videos eines bestimmten Kanals
  - `[pt-video]` - Zeigt einzelnes Video mit Details
- 2 Such-Shortcodes:
  - `[pt-search]` - Suchformular
  - `[pt-search-results]` - Suchergebnisse mit Paginierung
- Vollständige Admin-Einstellungsseite:
  - PeerTube-Instanz URL-Konfiguration
  - Standard-Kanäle-Verwaltung
  - Cache-Zeiteinstellungen
  - Verbindungstest-Funktion
  - Cache-Lösch-Funktion
- Intelligentes Caching-System:
  - Video-Listen: 5 Minuten (konfigurierbar)
  - Konfiguration: 24 Stunden (konfigurierbar)
  - Einzelne Videos: 10 Minuten
  - Suchergebnisse: 2 Minuten
- Unterstützung für benutzerdefinierte Plugin-Daten:
  - Sendeverantwortung (aus peertube-plugin-okas-dev)
  - Video-Nummer (aus peertube-plugin-okas-dev)
- Video-Suche per Video-Nummer: `[pt-video number="12345"]`
- Vollständige Metadaten-Anzeige:
  - Video-Titel und Thumbnail
  - Dauer/Länge
  - Kategorie (mit Mapping aus PeerTube-Konfiguration)
  - Veröffentlichungsdatum (relativ: "Vor 1 Tag")
  - Aufrufe-Zahl
  - Tags (bis zu 5 pro Karte, alle in Detail-Ansicht)
  - Sendeverantwortung
  - Video-Nummer
  - Beschreibung (mit HTML-Sanitization)
- Responsive Design:
  - Mobile: 1 Spalte
  - Tablet: 2 Spalten
  - Desktop: 3-4 Spalten
  - CSS Grid für optimales Layout
- Sicherheitsfeatures:
  - Input-Sanitization für alle Benutzereingaben
  - Output-Escaping für alle Ausgaben
  - Nonces für AJAX-Anfragen
  - Capability-Checks für Admin-Funktionen
  - wp_kses für sichere HTML-Beschreibungen
- Performance-Optimierungen:
  - Transient-basiertes Caching
  - Lazy Loading für Bilder
  - Respektierung der PeerTube API-Rate-Limits (50 req/10 sec)
  - Effiziente Datenbankabfragen
- Deutsche Übersetzung:
  - Vollständig übersetzt
  - POT-Datei für weitere Sprachen
  - German (de_DE) .po/.mo Dateien
- Umfangreiche Dokumentation:
  - README.md mit vollständiger Beschreibung
  - USAGE_DE.md für Endbenutzer
  - Inline-Code-Dokumentation
  - Shortcode-Beispiele in Admin-Oberfläche

### Technische Details
- Minimale WordPress-Version: 6.0
- Minimale PHP-Version: 7.4
- PeerTube API v1 Unterstützung
- Kompatibel mit allen modernen Browsern
- CORS-konforme API-Anfragen
- Gutenberg-kompatibel
- Classic Editor-kompatibel

### API-Endpunkte
- GET /api/v1/videos - Liste aller Videos
- GET /api/v1/videos/{id} - Einzelnes Video
- GET /api/v1/video-channels/{handle}/videos - Kanal-Videos
- GET /api/v1/search/videos - Video-Suche
- GET /api/v1/config - Instanz-Konfiguration

### Bekannte Einschränkungen
- Video-Suche per Nummer durchsucht maximal 500 Videos
- Föderierte Videos haben möglicherweise eingeschränkte Metadaten
- Cache muss manuell geleert werden für sofortige Updates

## [1.1.5] - 2025-01-XX

### Behoben
- Verbesserte Logik für automatische Video-Anzeige auf Video-Seite
- Zuverlässigere Seitenprüfung (per ID und URL)
- Verbesserte Qualität von Video-Thumbnails (verwendet previewPath statt thumbnailPath)
- Responsive Bilder mit srcset für bessere Performance
- Mobile Responsiveness für Video-Detail-Seite verbessert

### Hinzugefügt
- Unterstützung für `thumbnailUrl` aus PeerTube API
- `get_thumbnail_srcset()` Funktion für responsive Bilder
- Verbesserte CSS-Regeln für Bildqualität

## [1.1.4] - 2025-01-XX

### Behoben
- Verbesserte Paginierung mit modernem Design
- Runde Badges für Seitenzahlen
- Verwendung von Theme-Farben für Paginierung

## [1.1.3] - 2025-01-XX

### Hinzugefügt
- Separate Seite für Video-Ansicht
- Automatische Erstellung der Video-Seite bei Aktivierung
- Einstellung für Auswahl der Video-Seite

### Behoben
- Video-Links führen jetzt auf dedizierte WordPress-Seite statt auf PeerTube
- Auto-Display funktioniert nur auf konfigurierter Video-Seite

## [1.1.2] - 2025-01-XX

### Behoben
- "Bitte geben Sie einen Suchbegriff ein." wird nur beim ersten Besuch angezeigt
- Nach Suche wird die Nachricht ausgeblendet

## [1.1.1] - 2025-01-XX

### Hinzugefügt
- Anpassbare Button-Farben in Einstellungen
- WordPress Color Picker Integration
- CSS-Variablen für Button-Farben
- Anpassbarer Text für PeerTube-Button

## [1.1.0] - 2025-01-XX

### Hinzugefügt
- Parameter `columns` für alle Video-Shortcodes
- Unterstützung für 1-6 Spalten oder `auto`
- Automatische Anpassung auf mobilen Geräten

## [1.0.9] - 2025-01-XX

### Hinzugefügt
- Automatische Erstellung der Suchseite bei Aktivierung
- Einstellung für Auswahl der Suchseite
- Klickbare Hashtags führen zu Suchseite

## [1.0.8] - 2025-01-XX

### Hinzugefügt
- SVG-Icons statt Emoji für Metadaten
- "Sendeverantwortung:" Label vor Namen
- Optionale Anzeige von Aufrufen (standardmäßig aus)
- Verbesserte CSS-Stile für Icons

## [1.0.7] - 2025-01-XX

### Behoben
- Reihenfolge der Metadaten in Video-Karten angepasst
- Hashtags sind jetzt klickbar und führen zu Suchseite

## [Unreleased]

### Geplant für zukünftige Versionen
- Gutenberg-Blöcke als Alternative zu Shortcodes
- Video-Upload-Integration (wenn Berechtigungen vorhanden)
- Playlist-Unterstützung
- Live-Stream-Anzeige
- Erweiterte Filteroptionen
- AJAX-basierte Infinite-Scroll-Paginierung
- Video-Favoriten für eingeloggte Benutzer
- Kommentar-Integration
- Statistik-Dashboard im Admin-Bereich
- Multi-Instanz-Unterstützung

## Versionierungsschema

- MAJOR Version: Inkompatible API-Änderungen
- MINOR Version: Neue Funktionen (abwärtskompatibel)
- PATCH Version: Bugfixes (abwärtskompatibel)

[1.1.5]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.1.5
[1.1.4]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.1.4
[1.1.3]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.1.3
[1.1.2]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.1.2
[1.1.1]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.1.1
[1.1.0]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.1.0
[1.0.9]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.0.9
[1.0.8]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.0.8
[1.0.7]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.0.7
[1.0.0]: https://github.com/yarkolife/wp-peertube-plugin/releases/tag/v1.0.0
[Unreleased]: https://github.com/yarkolife/wp-peertube-plugin/compare/v1.1.5...HEAD

