# Consho Licence Check — Arbeitsregeln für Agenten

Gilt für jeden Programmier-Agenten, der in diesem Repository arbeitet. Werkzeugspezifische Dateien (etwa
`CLAUDE.md`) importieren nur diese Datei, damit es **eine** Fassung gibt.

## Was das ist

Freies Paket (MIT, L18) für die **Installation des Kunden**: Es prüft Lizenzschlüssel und die tägliche signierte
Laufzeitbestätigung für jedes bezahlte Modul, das sich bei ihm anmeldet — Consho Connect, das Lizenz-Paket, und
ebenso Erweiterungen fremder Hersteller, die über einen eigenen Consho-Shop verkaufen (L19). Das Gegenstück im
Verkaufsshop ist `webwerkwien/contao-consho-licensing-bundle` (proprietär).

**Hängt nicht am Consho-Kern**, nur an Contao — damit jede Contao-Erweiterung ihn nutzen kann.

**Die Spezifikation liegt im privaten Entwicklungsrepo des Kerns** (`contao-consho-bundle-dev`,
`docs/block-lizenz.md`: Block Lizenz, Abschnitt 4.5, Entscheidungen L18, L19, P1, P2, Plan LZ-6) und ist nicht
veröffentlicht. Wer Zugang hat: vor jeder Änderung dort nachlesen, nach jedem Plan die Zeile dort nachführen.
Ohne Zugang gelten README und Code.

## Grundsätze

- **Agent-neutral:** keine Anweisungen, Pfade oder Dateinamen, die an ein bestimmtes Werkzeug gebunden sind.
- **Vertraute öffentliche Schlüssel kommen aus dem Code des Moduls** (P1), nie aus der Konfiguration der
  Installation — sonst trüge ein Kunde sein eigenes Paar ein.
- **Kein Kopierschutz** (L4): PHP ist lesbar. Der Prüfer schützt ehrliche Kunden vor Missverständnissen und macht
  den Vertrag sichtbar; `APP_ENV=dev` sperrt nichts.
- **Der Schlüssel nie in eine Ausgabe, die in einen Chat gehen kann** — nicht in contao-ai-Antworten, nicht in
  Logs. Eingetragen wird er verdeckt auf der Konsole oder im Backend.
- **Übertragen werden nur Schlüssel, Installations-Kennung und Version** (L8, LZ-3 E1) — keine Domain, keine
  Shopdaten.
- **Keine echten Hosts** in Code, Tests, Kommentaren und Doku; als Testdaten `example.org`/`example.net`.

## Sprache und Stil

- Code-Kommentare englisch, Testnamen und Testkommentare deutsch, Commit-Nachrichten englisch.
- Klassenkommentare begründen Entscheidungen, nicht das Offensichtliche.

## Vor jedem Commit

```bash
vendor/bin/phpunit --no-coverage
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
```

Beide grün, gesamte Suite. Gegenproben (Mutation aus einer Kopie der Datei, Test muss rot werden, Kopie
zurück — nie `git checkout`/`restore`/`stash`/`reset`) an allen Stellen, an denen ein Fehler eine Sperre
wirkungslos, eine Fälschung annehmbar oder einen Schlüssel sichtbar machte. Commits mit expliziten Pfaden.
