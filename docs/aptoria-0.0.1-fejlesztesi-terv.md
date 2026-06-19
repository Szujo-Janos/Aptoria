# Aptoria 0.0.1 – nulláról épített fejlesztési terv

## 1. Alapirány

Az új Aptoria nem Jira-, Postman-, Linear-, Datadog- vagy klasszikus test management klón.

Az új rendszer célja:

> Evidence-first API QA és release döntéstámogató platform, amely a meglévő QA/dev eszközök mellett használható, és egy helyre gyűjti az API tesztelési bizonyítékokat, regressziós állapotokat, kockázatokat, release gate döntéseket és auditálható riportokat.

A régi Aptoria domain logikája hasznos referencia, de az új rendszert tiszta Laravel alapból kell felépíteni. A feltöltött `aptoria-ui` sablon vizuális alap, nem üzleti logikai alap.

## 2. Mit akarunk megoldani, amit a meglévő eszközök gyengén kezelnek?

### 2.1. Postman / Newman hiányosságai

Postman erős request építésben és gyűjteményfuttatásban, de gyenge ezekben:

- release döntéshez strukturált evidence csomag készítése;
- auditálható go / no-go döntés;
- endpoint coverage és blind spot kimutatás;
- accepted risk nyilvántartás;
- riportverziók és ügyfélnek átadható döntési csomag;
- több forrásból származó QA bizonyíték összefogása.

### 2.2. Jira / Linear hiányosságai

Ticketingre jók, de nem API QA evidence központok:

- egy ticket nem bizonyítja, hogy az API release készen áll;
- nehéz látni, melyik endpoint van ténylegesen letesztelve;
- a release döntés sokszor kommentekből, linkekből és kézi emlékezetből áll;
- nincs natív API contract / scan / snapshot / finding / evidence összefüggés.

### 2.3. OpenAPI / Swagger hiányosságai

Dokumentációra jó, de nem bizonyíték:

- a contract nem garantálja, hogy a valós API ugyanúgy működik;
- nincs természetes release readiness réteg;
- nem ad QA döntési történetet;
- nem kezeli a tesztelt és nem tesztelt endpointokat üzleti kockázat szerint.

### 2.4. Monitoring eszközök hiányosságai

Monitoring runtime állapotot figyel, de nem feltétlen QA release readiness eszköz:

- éles hibát jelez, de nem feltétlenül mutat pre-release blind spotot;
- nem release gate gondolkodású;
- nem épít auditált QA evidence csomagot.

### 2.5. Excel / kézi riport hiányosságai

Sok QA folyamat még táblázatokban él:

- könnyen szétesik;
- nincs audit trail;
- nincs automatikus evidence kapcsolat;
- nehéz bizonyítani, hogy mi alapján született a döntés.

## 3. Termékpozíció

Aptoria legyen:

- API QA evidence center;
- release readiness dashboard;
- blind spot detector;
- safe scan evidence gyűjtő;
- finding + evidence repository;
- release gate és decision package builder;
- auditálható QA munkatér;
- Postman/Jira/OpenAPI/monitoring melletti összekötő réteg.

Aptoria ne legyen:

- teljes Postman alternatíva;
- teljes Jira alternatíva;
- teljes security scanner;
- teljes APM/monitoring platform;
- általános project management rendszer.

## 4. Aptoria 0.0.1 célja

A 0.0.1 verzió célja nem a scan motor, nem a release gate, és nem a teljes QA rendszer.

A 0.0.1 célja:

> Tiszta Laravel alap + új UI sablon integráció + minimális admin shell + projektközpontú váz, amire a későbbi modulok ráépíthetők.

Ez legyen az új stabil alap, nem egy toldozott régi csomag.

## 5. 0.0.1 funkció scope

### 5.1. Benne legyen

- új Laravel projektstruktúra;
- `aptoria-ui` sablon assetjeinek rendezett beépítése;
- fő admin layout Blade-re bontva;
- auth layout;
- setup / first-run layout;
- login / logout;
- admin user létrehozás;
- kötelező első jelszócsere előkészítése;
- angol és magyar nyelvi alap;
- projekt lista;
- projekt létrehozás / szerkesztés / törlés alap;
- üres dashboard;
- üres Evidence Center oldal;
- üres Release Readiness oldal;
- üres Settings oldal;
- alap audit log tábla és service váz;
- verzió parancs: `php artisan aptoria:version`;
- health check parancs váz: `php artisan aptoria:health`;
- Windows/XAMPP telepítő/frissítő script;
- release ZIP szabályok betartása.

### 5.2. Ne legyen még benne

- safe scan motor;
- endpoint import;
- Postman/Newman import;
- OpenAPI validáció;
- assertion engine;
- snapshot compare;
- finding lifecycle;
- release gate döntés;
- PDF export;
- client portal;
- monitorozás;
- naptár;
- komplex jogosultsági rendszer.

Ezeket későbbi verziókban kell felépíteni, külön-külön stabil lépésekben.

## 6. Javasolt technikai alap

- PHP 8.2+
- Laravel 12 kompatibilis szerkezet
- Blade alapú szerveroldali UI
- SQLite alapértelmezett adatbázis fejlesztéshez
- később MySQL/MariaDB kompatibilitás
- Bootstrap/admin template alap a feltöltött `aptoria-ui` csomagból
- saját Aptoria domain réteg, UI-tól leválasztva
- angol alapnyelv, magyar választható

## 7. Javasolt mappaszerkezet

```text
app/
  Domain/
    Project/
    Workspace/
    Audit/
    Setup/
    User/
  Application/
    Project/
    Setup/
    Audit/
  Http/
    Controllers/
    Middleware/
    Requests/
  Models/
  Services/

resources/
  views/
    layouts/
      app.blade.php
      auth.blade.php
      setup.blade.php
      partials/
        sidebar.blade.php
        topbar.blade.php
        footer.blade.php
    auth/
    setup/
    dashboard/
    projects/
    evidence/
    release/
    settings/
  lang/
    en/messages.php
    hu/messages.php

public/
  assets/
    aptoria-ui/

routes/
  web.php
  console.php

scripts/
  update-windows-xampp.ps1
  update-linux.sh

docs/
  DEVELOPMENT_PLAN.md
  INSTALL_WINDOWS_XAMPP.md
  QA_CHECKLIST.md
```

## 8. UI sablon beépítési terv

A feltöltött sablonból első körben ezeket érdemes felhasználni:

- `index.html` – dashboard vizuális alap;
- `projects.html` / `projects-list.html` – projektlista és projektkártyák inspiráció;
- `auth-sign-in.html` – login oldal alap;
- `auth-new-pass.html` – első jelszócsere alap;
- `form-wizard.html` – későbbi setup/onboarding wizard alap;
- `calendar.html` – későbbi QA operations calendar alap;
- `tables-datatables-basic.html` – későbbi endpoint/finding/evidence táblák alap;
- `misc-sweet-alerts.html` – megerősítő műveletek;
- `pages-timeline.html` – audit/evidence timeline alap.

Nem kell átvenni:

- demo üzleti oldalakat;
- chat/outlook/forum/invoice funkciókat;
- fölösleges skin variánsokat;
- minden minta HTML oldalt alkalmazás route-ként.

## 9. Adatmodell 0.0.1-ben

### users

- id
- name
- email
- password
- role
- locale
- timezone
- first_login_at
- last_login_at
- password_change_required
- timestamps

### projects

- id
- user_id
- name
- slug
- description
- base_url
- report_client_name
- report_organization
- is_active
- timestamps

### audit_logs

- id
- project_id nullable
- user_id nullable
- event_type
- action
- severity
- subject_type nullable
- subject_id nullable
- subject_label nullable
- summary
- ip_address nullable
- user_agent nullable
- metadata_json nullable
- occurred_at
- timestamps

### settings

- id
- key
- value_json
- group
- type
- is_public
- timestamps

## 10. Route terv 0.0.1-ben

```text
GET  /                         landing vagy redirect
GET  /setup                    first-run setup
POST /setup/admin              admin létrehozás
POST /setup/finish             telepítés lezárása
GET  /login                    login
POST /login                    login submit
POST /logout                   logout
GET  /profile                  profil
GET  /profile/password         kötelező jelszócsere
POST /profile/password         jelszó mentése
GET  /dashboard                dashboard
GET  /projects                 projektlista
GET  /projects/create          új projekt
POST /projects                 projekt mentés
GET  /projects/{project}       projekt áttekintés
GET  /projects/{project}/edit  projekt szerkesztés
PUT  /projects/{project}       projekt frissítés
DELETE /projects/{project}     projekt törlés
GET  /evidence                 evidence center placeholder
GET  /release-readiness        release readiness placeholder
GET  /settings                 settings placeholder
GET  /audit-log                audit log lista
GET  /language/{locale}        nyelvváltás
```

## 11. Menü 0.0.1-ben

Fő sidebar:

- Dashboard
- Projects
- Evidence Center
- Release Readiness
- Audit Log
- Settings
- Help / How it works

Későbbi, de még rejtett vagy disabled menüpontok:

- Endpoint Inventory
- Safe Scans
- Assertions
- Snapshots
- Findings
- Reports
- Client Portal
- Monitors
- Calendar

## 12. Fejlesztési szabályok

- A sablon csak UI, nem üzleti logika.
- Hardcode szöveg ne kerüljön Blade-be, minden fordításból jöjjön.
- Domain döntés ne controllerben legyen.
- Minden destruktív művelethez később typed confirmation kell.
- Auth token/jelszó/szenzitív adat soha ne menjen ki nyersen UI-ba vagy exportba.
- Projekt legyen a fő munkatér.
- Minden release ZIP kumulatív legyen.
- `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock`, `storage/app/setup-token.txt` ne kerüljön release ZIP-be.
- `public/assets/aptoria-ui/vendor` vagy a sablon vendor assetjei maradjanak a ZIP-ben, ha a UI működéséhez kellenek.

## 13. Első fejlesztési csomag neve

Javasolt induló verzió:

```text
aptoria-0.0.1.zip
```

Javasolt cím:

```text
Aptoria v0.0.1 – Clean Template Shell & Product Foundation
```

## 14. 0.0.1 elfogadási feltételek

A csomag akkor elfogadható, ha:

- friss XAMPP/Laravel környezetben telepíthető;
- login működik;
- setup lezárható;
- admin létrejön;
- dashboard megnyílik;
- sidebar/topbar/footer az új UI sablonból épül;
- projekt létrehozható és listázható;
- magyar/angol nyelvváltás működik;
- audit logba bekerül legalább login, logout, project created, project updated;
- nincs régi Aptoria UI maradvány;
- nincs régi 1.1.x vagy elvetett 2.0.0 kódirány;
- ZIP nem tartalmaz tiltott runtime fájlokat.

## 15. Rövid QA checklist 0.0.1-hez

1. Nyisd meg az alkalmazást friss környezetben.
2. A rendszer irányítson setup oldalra.
3. Hozz létre admin felhasználót.
4. Zárd le a setupot.
5. Jelentkezz be.
6. Ellenőrizd, hogy az új UI sablon sidebar/topbar/dashboard megjelenik.
7. Válts magyar és angol nyelv között.
8. Hozz létre egy projektet.
9. Szerkeszd a projektet.
10. Nézd meg, hogy az audit logban megjelentek-e az események.
11. Jelentkezz ki, majd újra be.
12. Ellenőrizd, hogy nincs 500-as hiba, hiányzó asset vagy régi Aptoria layout.

## 16. Következő verziók előzetes sorrendje

### 0.0.2 – Endpoint Inventory Foundation

- environments;
- auth profile váz;
- endpoint CRUD;
- endpoint import preview váz;
- project dashboard első valódi kártyái.

### 0.0.3 – Safe Scan Evidence Foundation

- SafeProbeService;
- ScanRun/ScanResult;
- GET/HEAD-only probe;
- private network guard;
- token masking;
- response metadata.

### 0.0.4 – Assertion, Snapshot & Finding Foundation

- assertion rule váz;
- snapshot mentés;
- snapshot compare;
- finding alap;
- evidence attachment alap.

### 0.0.5 – Release Readiness Foundation

- readiness score;
- blind spot detector;
- release gate váz;
- Markdown/HTML riport váz;
- decision package JSON.

## 17. Összegzés

Az első lépésben nem funkciótömeget kell építeni, hanem tiszta alapot.

Aptoria 0.0.1 feladata:

> új Laravel alap, új UI sablon, stabil auth/setup/project shell, tiszta domain irány, és olyan szerkezet, amelyből később bizonyíték-alapú API QA platform építhető.

Ez lesz az új Aptoria nulladik stabil alapja.
