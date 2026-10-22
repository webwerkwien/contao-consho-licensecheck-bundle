# Changelog

Alle nennenswerten Änderungen an diesem Paket.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung an [Semantic Versioning](https://semver.org/lang/de/).

## [0.1.2] – 2026-10-22

### Geändert

- `consho:licensecheck:set` und `consho:licensecheck:refresh` erklären sich als unumkehrbar (`irreversible` im
  `#[AiContract]`): Sie schicken den Schlüssel an den Shop, und der gibt dieser Installation den Platz der Lizenz,
  den nur eine Freigabe wieder löst. Ab contao-ai backend v0.22.1 laufen sie deshalb nicht im Backend-Chat, sondern
  über `contao-ai-cli ext run`.

## [0.1.1] – 2026-10-22

### Geändert

- `AGENTS.md` und `CLAUDE.md` (Arbeitsregeln für Entwickler) sind nicht mehr Teil des Pakets.

## [0.1.0] – 2026-10-16

Erste veröffentlichte Version. Jeder Eintrag beschreibt den Stand zu diesem Release; der Plan steht in Klammern
am Ende (die Spezifikation gehört zum Entwicklungsrepo des Consho-Kerns und ist nicht veröffentlicht).

### Hinzugefügt

- Grundgerüst: Composer-Paket (MIT), nur an Contao gebunden, nicht an den Consho-Kern (LZ-6)
- **Anmeldung bezahlter Erweiterungen** über `LicensedProductInterface` mit Produkt-Kennung, Version und den
  öffentlichen Schlüsseln des Verkäufers — aus dem Code der Erweiterung, nie aus der Konfiguration (LZ-6)
- **Sperre** `LicenseGate`: offline bei jedem Aufruf neu geprüft — signiert, Format-Version 1, eigener Schlüssel, eigene Installation,
  aktiv, Frist nicht vorbei, Uhr nicht zurückgestellt; in `dev` frei (LZ-6)
- **Abholung** der täglichen Bestätigung mit Schlüssel, Installations-Kennung und Version; nicht erreichbar,
  gedrosselt, unbekannt oder unsigniert behält die letzte Bestätigung (LZ-6)
- **Stündlicher Cron**, jede Installation in ihrer eigenen Stunde; **Startseite** mit Sperre und Warnung nach sieben
  Tagen ohne Antwort; **System → Lizenzen**; Befehle `consho:licensecheck:set`, `status`, `refresh`, `release` mit
  contao-ai-Verträgen (LZ-6)
- **Nie über das Abo-Ende:** Die Sperre prüft `activeUntil` selbst mit, statt sich allein auf den Shop zu verlassen
  (LZ-7)

### Behoben

- Der stündliche Cron fragt jede Erweiterung, auch wenn eine scheitert; der erste Fehler kommt danach (LZ-7)
- „System → Lizenzen": ein Text, der kein Schlüssel ist, wird in der Sprache des Benutzers abgelehnt; die Knöpfe
  brechen im schmalen Fenster um, statt abgeschnitten zu werden (LZ-7)
