# Changelog

Alle nennenswerten Änderungen an diesem Paket.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung an [Semantic Versioning](https://semver.org/lang/de/).

## [Unveröffentlicht]

Noch keine Version veröffentlicht. Jeder Eintrag beschreibt den heutigen Stand; der Plan steht in Klammern am
Ende (Spezifikation `docs/block-lizenz.md` im Repo des Consho-Kerns).

### Hinzugefügt

- Grundgerüst: Composer-Paket (MIT), nur an Contao gebunden, nicht an den Consho-Kern (LZ-6)
- **Anmeldung bezahlter Erweiterungen** über `LicensedProductInterface` mit Produkt-Kennung, Version und den
  öffentlichen Schlüsseln des Verkäufers — aus dem Code der Erweiterung, nie aus der Konfiguration (LZ-6)
- **Sperre** `LicenseGate`: offline bei jedem Aufruf neu geprüft — signiert, eigener Schlüssel, eigene Installation,
  aktiv, Frist nicht vorbei, Uhr nicht zurückgestellt; in `dev` frei (LZ-6)
- **Abholung** der täglichen Bestätigung mit Schlüssel, Installations-Kennung und Version; nicht erreichbar,
  gedrosselt, unbekannt oder unsigniert behält die letzte Bestätigung (LZ-6)
- **Stündlicher Cron**, jede Installation in ihrer eigenen Stunde; **Startseite** mit Sperre und Warnung nach sieben
  Tagen ohne Antwort; **System → Lizenzen**; Befehle `consho:licensecheck:set`, `status`, `refresh`, `release` mit
  contao-ai-Verträgen (LZ-6)
