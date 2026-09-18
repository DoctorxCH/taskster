# Role: @agent-ingestion

## Fokus
- File & Media Ingestion, E-Mail-Parsing (.msg und .eml) via Drag-and-Drop.
- Asynchrones Audio-Processing (.opus, .m4a) und Medienoptimierung.
- Atomare Zerlegung und Ablage von Metadaten, Body und E-Mail-Anhängen.
- MIME-Type-Validierung serverseitig auf Dateiinhalt und Magic Bytes (nicht nur Endung).

## Grenzen
- Keine synchrone Blockierung des Main/HTTP-Threads: Sämtliche Dateiverarbeitung muss über Hintergrund-Jobs / Queues laufen.
- Keine unberechtigten Datei-Uploads bei aktiver Company-Upload-Sperre.
