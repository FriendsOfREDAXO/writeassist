# Changelog

## 3.4.0 - 2026-09-10
### Hinzugefügt
- **SEO-Felder in der Massenübersetzung:** Ist YRewrite installiert, lassen sich in der Massenübersetzung optional die SEO-Felder Titel und Beschreibung (`yrewrite_title`, `yrewrite_description`) der ausgewählten Kategorien und Artikel mitübersetzen. Auswahl per Checkbox.

### Geändert
- **Auswahl in der Massenübersetzung vereinheitlicht:** Die bisherigen Radiobuttons (Artikel & Kategorien / Nur Artikel / Nur Kategorien) sind jetzt Checkboxen und bilden zusammen mit den SEO-Feldern eine gemeinsame Liste. Artikel und Kategorien können unabhängig gewählt werden.
- **Wording:** „Nur noch nicht übersetzte Namen" → „Bereits übersetzte Werte überspringen"; Hinweis- und Warntexte sprechen jetzt von „Einträgen"/„Werten" statt nur „Namen", da neben Namen auch SEO-Felder übersetzt werden. In den Einstellungen sind die Optionen des Übersetzungs-Dienstes klarer benannt.

## 3.3.1 - 2026-09-10
### Hinzugefügt
- **Optionaler Kontext für KI-Übersetzungen:** In den Einstellungen lässt sich bei Text-KI als Übersetzungs-Dienst ein freier Hintergrund-Text hinterlegen (z.B. Art der Website, feste Begriffe/Abkürzungen, die unübersetzt bleiben sollen). Wird jedem KI-Übersetzungs-Prompt mitgegeben – Einzelübersetzung, Auto-Übersetzen und Massenübersetzung gleichermaßen. Hilft besonders kleineren Modellen, Eigennamen/Akronyme (z.B. Vereinskürzel) nicht frei zu "übersetzen".

### Behoben
- **"Nur noch nicht übersetzte Namen" fehlte in der Abschluss-Anzeige der Massenübersetzung:** Seit der Umstellung auf Batches (3.3.0) filterte die Option weiterhin korrekt, aber die Anzahl der dadurch übersprungenen Einträge wurde nicht mehr an die Ergebnis-Anzeige übergeben – es wirkte dadurch, als würde die Option ignoriert. Zähler ist jetzt wieder in der Zusammenfassung sichtbar.

## 3.3.0 - 2026-09-10
### Geändert
- **Massenübersetzung läuft jetzt in Batches statt in einem langen Request:** Bisher übersetzte ein einziger Request alle betroffenen Artikel/Kategorien komplett synchron durch – bei vielen Einträgen ein Risiko für PHP-Timeouts und einen für die Dauer blockierten Browser-Tab. Die Seite ermittelt jetzt zunächst die Arbeitsliste und arbeitet sie in kleinen Batches (5 Einträge) ab, mit Live-Fortschrittsanzeige und einem Abbrechen-Button.

### Behoben
- **Reasoning-Modelle konnten Artefakte in Übersetzungen hinterlassen:** Bei als Text-KI eingebundenen "Thinking"-Modellen (z.B. Qwen3 über ai_platform) konnten Marker wie `<think>...</think>` oder ein angehängtes `think`/`/think` unbemerkt Teil des übersetzten Artikel- oder Kategorienamens werden. Der Übersetzungs-Prompt schaltet den Thinking-Modus jetzt explizit per `/no_think` ab (bei Modellen, die das nicht unterstützen, wirkungslos aber unschädlich), zusätzlich werden bekannte Reasoning-Marker aus der Antwort entfernt.

## 3.2.2 - 2026-09-10
### Behoben
- **Auto-Übersetzen blieb bei Text-KI ohne DeepL-Key inaktiv:** "Bei Neuanlage" und "Bei Umbenennung" prüften intern immer nur, ob ein DeepL-API-Key hinterlegt ist – unabhängig vom oben gewählten Übersetzungs-Dienst. War als Dienst "Text-KI" gewählt und ein KI-Provider korrekt konfiguriert, aber kein DeepL-Key hinterlegt, blieben beide Optionen dadurch stillschweigend wirkungslos, obwohl die eigentliche Übersetzung (`translateText()`) den KI-Provider korrekt genutzt hätte. Die Verfügbarkeitsprüfung berücksichtigt jetzt den gewählten Dienst.
- Diverse Texte in den Einstellungen und der README erwähnten noch "per DeepL", obwohl seit 2.5.0 auch die Text-KI als Übersetzungs-Dienst gewählt werden kann. Texte auf den gewählten Dienst umformuliert (inkl. Sidebar-Warnung bei fehlender Konfiguration).

## 3.2.1 - 2026-09-10
### Behoben
- **Toggle-Switches zeigten noch die leere Dropdown-Box dahinter:** Core umschließt jedes `<select>` mit einem eigenen `.rex-select-style`-Wrapper, der selbst wie eine Dropdown-Box aussieht (Rahmen + Pfeil per Theme-CSS). Ein `hide()` nur auf dem `<select>` reichte daher nicht. Jetzt wird der komplette Wrapper versteckt.

## 3.2.0 - 2026-09-10
### Geändert
- **Einstellungsseite überarbeitet:** Die Ja/Nein-Auswahlfelder unter "Integrationen" (Info Center Widget, TinyMCE-Plugin, Auto-Übersetzen bei Neuanlage/Umbenennung) sind jetzt echte Toggle-Switches statt Dropdowns.
- **DeepL-Fieldset reagiert auf den gewählten Übersetzungs-Dienst:** Ist oben "Text-KI" als Übersetzungs-Dienst gewählt, wird der DeepL-Bereich ausgegraut und mit einem Hinweis versehen, da der DeepL-Key dann von keiner Funktion des Addons mehr benötigt wird. Zurück auf "DeepL" gewechselt, wird der Bereich wieder normal angezeigt.

## 3.1.0 - 2026-09-10
### Geändert
- **Massenübersetzung nutzt jetzt ebenfalls den KI-Fallback:** Bisher war die Massenübersetzung (Backend > WriteAssist > Massenübersetzung) die einzige Stelle im Addon, die zwingend einen DeepL-API-Key voraussetzte, obwohl Einzelübersetzung und Auto-Übersetzung bei Neuanlage seit 2.5.0 auch über eine Text-KI (Gemini/OpenAI/OpenWebUI/ai_platform) laufen können. Ist in den Einstellungen als Provider "Text-KI" gewählt und korrekt konfiguriert, nutzt die Massenübersetzung jetzt ebenfalls diese – ohne DeepL-Key.

### Behoben
- **Echter Bug in der Text-Generierung:** Die "Custom Prompt"-Aktion im Generator rief eine nicht existierende Methode auf `GeminiApi` auf und ist dadurch bei jedem Aufruf mit einem Fehler abgebrochen. Behoben.
- Diverse Robustheits-Fixes (fehlschlagendes JSON-Encoding vor einem API-Call wird jetzt als klare Fehlermeldung statt als leerer, stiller Request behandelt; PCRE-Fehler beim Aufräumen von KI-Antworten führen nicht mehr zu einem ungültigen Zwischenwert).

## 3.0.0 - 2026-09-07
### Hinzugefügt
- **KI-Buttons an normalen Formularfeldern**: Textareas und Inputs ohne WYSIWYG-Editor bekommen per CSS-Klasse einen KI-Button – `watext` für Generieren/Umschreiben/Zusammenfassen/Erweitern/eigener Prompt, `watranslate` für Direktübersetzung. Beide Klassen sind kombinierbar, funktionieren in Modulen, YForm, MForm und eigenen Backend-Seiten, und erkennen auch nachträglich eingefügte Felder (z.B. per MBlock) automatisch. Neue Demo-Seite *Feld-Widget Demo* zeigt alle Varianten live.
- **ai_platform-Addon als Provider**: Ist [ai_platform](https://github.com/FriendsOfREDAXO/ai_platform) installiert, kann WriteAssist dessen zentral verwaltetes Text-Profil nutzen – keine eigenen API-Keys nötig. Gilt für Übersetzung, Generierung und die neuen Feld-Widgets gleichermaßen (alle Aufrufer laufen über dieselbe Provider-Factory).
- **Feldlimit-sicher**: Ein vorhandenes `maxlength`-Attribut wird beim Einsetzen von KI-Ergebnissen respektiert – bei Bedarf wird sichtbar gekürzt statt still Daten zu verlieren.

### Geändert
- **Verbindungstest ohne Speichern**: Der „Verbindung testen"-Button in den Einstellungen testet jetzt den aktuellen, noch ungespeicherten Formularstand statt nur der gespeicherten Config (analog zu ai_chat) – ein Key lässt sich so vor dem Speichern prüfen.

### Entfernt
- **Code-Generator (Admin-Only) entfernt.** Die dedizierte Coding-Assistent-Seite samt Code-Generieren/-Erklären/-Verbessern/-Fragen-Aktionen ist raus – Textgenerierung, Umschreiben und Übersetzen bleiben unverändert erhalten. Breaking Change für alle, die die Seite aktiv genutzt haben.

## 2.5.1 - 2026-06-04
### Behoben
- **InfoCenter Widget Bugfix:** Das Widget stürzt nicht mehr ab oder zeigt Fehler an, wenn DeepL deaktiviert ist. Es respektiert nun vollständig das in den Einstellungen gewählte Translaton-Backend (Text-KI vs. DeepL) und blendet auch den Deepl-Ladebalken entsprechend sauber aus.
- **Generator Tab:** Nutzt nun ebenfalls die globale AI-Factory (OpenAI/OpenWebUI Kompatibilität) statt hart auf Gemini zu setzen.

## 2.5.0 - 2026-06-04
### Hinzugefügt
- **LLM/KI für automatische Übersetzungen:** WriteAssist entkoppelt sich auf Wunsch vom reinen DeepL-Zwang! In den WriteAssist-Einstellungen lässt sich nun einstellen, ob für alle internen Übersetzungen (sowie externe Plugins wie `yform_lang_fields`) "DeepL" oder die eingestellte "Text-KI" (z.B. Gemini, OpenAI, OpenWebUI) genutzt werden soll.
- Richtext-Übersetzung: Gibt man der KI (wie Gemini) den Job, Websites/TinyMCE-Inhalte zu übersetzen, extrahiert WriteAssist den Text samt HTML und achtet darauf, die Formatierungen strikt beizubehalten, statt sie zu zerstückeln. Man braucht damit nicht zwingend eine separate DeepL Subscription für REDAXO-Übersetzungen, sofern man ohnehin API-Tokens der KIs nutzt!

### Geändert
- Das TinyMCE Übersetzungs-Plugin wurde umbenannt, da es nicht mehr ausschließlich für DeepL ist, sondern die API dynamisch entscheidet.

## [2.4.1] - 2026-04-21
### Added
- **DeepL-Nutzungsanzeige**: Einstellungs-Sidebar zeigt nun den aktuellen Zeichenverbrauch als Fortschrittsbalken an (automatisch über die DeepL `/usage`-API abgerufen). Funktioniert mit Developer Plan und Pro-Accounts. Closes #8.
- **DeepL-Nutzungsanzeige im Widget**: Der Übersetzer-Tab im InfoCenter-Widget zeigt ebenfalls den aktuellen Zeichenverbrauch als kompakten Fortschrittsbalken an.

### Changed
- **DeepL Developer Plan**: Hinweis auf den neuen DeepL Developer Plan (bis zu 1.000.000 Zeichen kostenlos, einmalig) in Einstellungen und README aktualisiert – der bisherige Free Plan mit 500.000 Zeichen/Monat wurde von DeepL eingestellt.

## [2.4.0] - 2026-04-21
### Fixed
- **Bulk-Translate**: Checkbox „Nur noch nicht übersetzte Namen" saß außerhalb des Fieldsets – Bootstrap-3-Struktur korrigiert (`div.checkbox > label > input` statt `label.checkbox > input`)

### Changed
- **InfoCenter-Widget**: Tabs werden nur noch angezeigt, wenn der zugehörige Service konfiguriert ist (Übersetzer-Tab nur bei vorhandenem DeepL-Key, Generator-Tab nur bei konfigurierter KI). LanguageTool (Improve) ist immer verfügbar (kostenlose Public-API).
- **AI-Provider**: Neue Option „Deaktiviert" im Provider-Select deaktiviert den Generator-Tab explizit.
- **AI-Provider**: Default-URL für OpenWebUI-Provider war `http://localhost:3000` und sorgte dafür, dass der Generator-Tab stets als konfiguriert galt – jetzt leer.
- **AI-Provider**: Provider-Einstellungsblöcke und Verbindungstest-Button werden server- und clientseitig korrekt aus-/eingeblendet (bei „Deaktiviert" alles versteckt).
- **Settings**: Notices mit HTML-Links (`api_key_notice`, `gemini_api_key_notice`) nutzen jetzt `rex_i18n::rawMsg()` statt `i18n()`, damit Links korrekt gerendert werden.
- **Verbindungstest**: Hinweis ergänzt, dass vor dem Test gespeichert werden muss.
- **Sidebar**: KI-Provider zeigt bei „Deaktiviert" nun korrekt „Deaktiviert" statt fälschlicherweise den vorherigen Provider als konfiguriert.

### Added
- **OpenAI-Provider**: Neuer Provider „OpenAI (ChatGPT)" mit dediziertem Formularblock (API-Key + Modell-Auswahl). Nutzt die offizielle OpenAI-API (`api.openai.com/v1`) – keine Base-URL-Eingabe nötig. Verfügbare Modelle: `gpt-4o-mini`, `gpt-4o`, `o4-mini`, `o3`.
- **Gemini-Modelle aktualisiert**: Modell-Auswahl enthält jetzt aktuelle Modelle (Gemini 2.5 Flash, 2.5 Flash Lite, 2.5 Pro, 3 Flash Preview, 3.1 Pro Preview). `gemini-3-pro-preview` wurde entfernt (eingestellt am 09.03.2026).

## [2.3.1] - 2026-03-11
### Fixed
- Syntaxfehler (fehlendes Semikolon) in `pages/settings.php` – führte zu Weißseite im Backend

## [2.3.0] - 2026-03-11
### Added
- **Auto-Übersetzen bei Umbenennung**: Neue Option `Übersetzung bei Umbenennung`, die Artikel- und Kategorienamen automatisch per DeepL in alle anderen Sprachen übersetzt, wann immer der Name gespeichert wird.
- Neue Methode `AutoTranslateService::isRenameEnabled()` für die neue Funktion.
- `ART_UPDATED`- und `CAT_UPDATED`-Extension-Point-Handler in `boot.php`.
- Einstellung `translate_on_rename` in der Backend-Einstellungsseite (Bereich Integrationen).
- Sidebar in den Einstellungen zeigt nun beide Auto-Übersetze-Optionen separat an.

## [2.2.1] - 2026-03-10
### Fixed
- **Bulk-Translate**: JS wird jetzt korrekt über `boot.php` geladen (statt in der Page-Datei, wo es zu spät war)
- **Bulk-Translate**: API auf `published = false` gesetzt (nur Backend)
- **Bulk-Translate**: `rex-api-call` korrekt als GET-Parameter in der URL

## [2.2.0] - 2026-03-10
### Added
- **Massenübersetzung**: Neue Backend-Seite zum Übersetzen bestehender Artikel- und Kategorienamen aus einer Quellsprache in alle anderen activen Sprachen via DeepL.
- **Option „nur unübersetzte"**: Überspringt Einträge, die in der Zielsprache bereits einen abweichenden Namen haben.
- Neue `AutoTranslateService`-Methoden `getTargetCode()` und `getSourceCode()` als öffentliche Aliases für die DeepL-Codemapping-Logik.

## [2.1.0] - 2026-03-10
### Added
- **Auto-Übersetzen**: Neue Artikel und Kategorien werden beim Anlegen automatisch per DeepL in alle aktiven Sprachen übersetzt.
- **Einstellung**: Option `enable_auto_translate` in den Einstellungen, erfordert hinterlegten DeepL-API-Key.
- **Robustheit**: Übersetzung läuft nach Abschluss aller REDAXO-internen Datenbank-Inserts (via `register_shutdown_function`), verhindert Überschreiben durch REDAXO-Core.

### Changed
- **Settings-Seite**: Zweispaltiges Layout mit Status-Sidebar (Auto-Übersetzen, API-Status, Integrationen).
- **JavaScript**: Settings-JS in separate Datei `assets/js/writeassist-settings.js` ausgelagert, `rex:ready`-Event statt `DOMContentLoaded`.

## [2.0.0] - 2026-01-22
### Added
- **AI Provider Architecture**: Support for Google Gemini and OpenAI Compatible (OpenWebUI/Ollama) providers.
- **TinyMCE Integration**: New `writeassist_generate` plugin for AI text generation, rewriting, summarizing, and expanding directly within the editor.
- **Prompt Management**: Save, load, and delete custom prompt templates in the Generator.
- **Improved Generator UI**: Added "Instructions" field alongside the Topic field for more precise control.
- **InfoCenter Widget**: Added a new "Generator" tab to the dashboard widget for quick access to AI tools.
- **Security Awareness**: Dynamic labels in settings and code generator to indicate which service is processing data.

### Changed
- **Refactoring**: Moved specific provider logic into individual Provider classes (`WriteAssistAiProviderGemini`, `WriteAssistAiProviderOpenAiCompatible`) behind a Factory.
- **UI Enhancements**: Improved labels and descriptions in backend settings and tools.
- **Fixes**: Resolved method duplication issues in API wrapper.
