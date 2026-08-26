# CLAUDE.md

Aquest fitxer dona indicacions a Claude Code (claude.ai/code) per treballar amb el codi d'aquest repositori.

## Estat del projecte

Les 5 fases estan fetes — 92/92 tests de Pest + 9/9 tests de Vitest passen. Tant el flux complet d'Operari com el Dashboard d'Admin (amb dades reals sembrades, filtres, i el toggle taula/gràfic) s'han provat de cap a cap en un navegador real (headless-Chromium) sense errors de consola. No hi ha cap Fase 6 planificada; la feina que ve ara és millora/manteniment sobre una especificació completa, no per fases com la 1-5 — tot i així, segueix aplicant la regla d'or (explicar → esperar aprovació → implementar) per a qualsevol cosa que no sigui trivial.

**Forat real conegut, parcialment tancat, en falta més:** encara no hi ha pàgines Vue d'Admin per gestionar OrderFabrication/Equipment/User — `Admin/ProjectIndex.vue` i `Admin/SectionIndex.vue` (+ `Admin/QuestionIndex.vue` per a les preguntes d'una secció) ja existeixen (construïdes un recurs cada vegada, seguint la petició explícita de l'usuari de fer-ho "pas a pas"; no construeixis la resta de manera especulativa, espera que t'ho demanin). Els endpoints JSON `/api/*` per a tots aquests ja existeixen i funcionen; només falta la interfície. Quan et demanin construir el següent, segueix el patró de `ProjectIndex.vue`: una taula + un formulari modal d'afegir/editar, reutilitzant el CRUD `/api/*` existent.

**Model de dades central: una Secció *és* una paraula de la descripció d'un projecte, i porta el seu propi qüestionari.** Calen dues iteracions per arribar-hi. Primer pas: relació N-a-N Secció ↔ Projecte (un projecte només fa servir un subconjunt de les Seccions globals, no totes — pivot `project_section`, `Project::sections()`/`Section::projects()`). Segon pas — el important: l'usuari va revelar que el que semblava un camp de text lliure separat "Descripció" (els seus exemples: "AH17DV2 TS USB") **no** és independent de les Seccions — cada una d'aquestes paraules (p. ex. "AH17DX2") **és** una Secció, i triar les paraules de descripció d'un projecte *és* triar quines Seccions/qüestionaris s'hi apliquen ("les seccions no han de sortir en el llistat ja que pertanyen a la descripció del projecte"). Per això hi va haver una breu desviació on va existir un model `DescriptionTag` independent — es va eliminar; no el tornis a crear. Aquí només hi ha un concepte: **Secció**, que fa doble feina com a paraula de visualització i com a propietària del qüestionari.

L'ordre importa en aquesta descripció combinada ("AH17DV2 TS USB" és una seqüència concreta, no alfabètica), i una llista plana de checkboxes no escala a mesura que el catàleg creix ("si és checkbox així serà molt llarg"). Per això `project_section` porta una columna `order`; `Project::sections()` és `withPivot('order')->orderByPivot('order')`; `Admin\ProjectController` té un mètode privat `syncSections()` que mapeja la posició de l'array `section_ids` rebut a l'`order` del pivot (l'ordre de l'array *és* la font de veritat — el frontend ha d'enviar-les en l'ordre de visualització desitjat, no alfabèticament). El camp "Descripció" de `ProjectIndex.vue` és un cercador-per-afegir amb autocompletar (escriure filtra el catàleg `sections` al client, amb un "+" per fer créixer el catàleg) més una `<ol>` ordenada de xips amb controls ↑/↓/× — ja no hi ha cap bloc de checkboxes "Seccions" ni columna de taula separada, aquest selector *és* el selector de seccions.

**Les preguntes tenen una `category` fixa (`App\Enums\QuestionCategory`: Estètica / FuncionalMecanica / Electrònica), i no totes les seccions necessiten les tres** ("el 90% dels projectes sí, però algun no pot tenir la part electrònica"). `questions.category` és obligatòria (no existeix l'estat "sense categoria"), però una secció simplement té les preguntes que té en les categories que li apliquin — no cal cap indicador per "desactivar" una categoria, la seva absència entre les preguntes de la secció ja n'hi basta. `Operari\EquipmentController@show` retorna cada pregunta amb la seva `category`; `FormCheck.vue` les agrupa en subcapçaleres per secció, ometent les categories buides. `Admin/QuestionIndex.vue` (s'hi arriba clicant una secció a `Admin/SectionIndex.vue`) és on es defineix la categoria de cada pregunta.

L'ordre de les preguntes dins d'una secció es defineix **arrossegant les files**, no amb un camp numèric manual — deixar anar una fila crida `POST /api/sections/{section}/questions/reorder` amb l'array complet i ordenat `question_ids`; `Admin\QuestionController@reorder` mapeja directament la posició de l'array a la columna `order` de cada pregunta en un sol pas, validant primer que cada ID pertany realment a aquesta secció. Les preguntes noves s'afegeixen al final (`order: questions.length` en el moment de crear-les) en lloc de demanar a l'usuari que escrigui un número.

L'arrossegar en si fa servir **`vue-draggable-plus`** (un embolcall de SortableJS), no un `draggable`/`dragover` d'HTML5 fet a mà — un primer intent fet a mà només desplaçava la fila exacta sota el punter, no totes les files entre l'origen i el destí de l'arrossegament, cosa que l'usuari va detectar de seguida ("nomes es mou el primer o l'últim"). No facis servir el `vuedraggable` clàssic (l'embolcall més antic de SortableJS/Vue.Draggable) si això torna a sortir en un altre lloc — els seus camps `main`/`module` apunten tots dos a builds UMD sense cap entrada ESM real, cosa que feia que Vite no pogués deduplicar Vue i gairebé doblava la mida del paquet `app.js`; `vue-draggable-plus` té un `exports` d'ESM correcte i el seu pes de SortableJS es queda dins del chunk propi d'aquesta pàgina en lloc d'escapar-se cap al paquet compartit.

La Família es va quedar com un concepte separat i més simple: una única opció per projecte (model `Family`, FK `projects.family_id`, obligatòria), amb el mateix patró d'interfície "+"-per-fer-créixer-el-catàleg que les Seccions, gestionat per `Admin\FamilyController` (només `index`/`store`/`destroy`). `FamilyController@destroy` bloqueja eliminar una família que encara estigui en ús per algun projecte (422, no un error de restricció de BD). Els valors reals del catàleg que l'usuari va donar (`FamilySeeder`, i les Seccions amb codi de component sembrades a `ProjectSeeder`) són llistes parcials ("no tota la llista") — cal esperar que se n'afegeixin més sobre la marxa des de la interfície, no tractar-les com a exhaustives. `ProjectFactory`/`FamilyFactory` generen valors únics sintètics (no les paraules reals del catàleg) precisament perquè els tests que creen molts registres en un bucle no esgotin un conjunt petit de dades de prova i topin amb la restricció d'unicitat de la BD.

**Refinament posterior al llançament (després de les 5 fases originals):** l'usuari va aclarir el flux real d'Operari després de veure'l funcionant — els números d'OF són únics a tota l'aplicació (format com `2026/01/0000123`, mai repetit, no només únic dins d'un projecte) i un operari cerca directament pel número d'OF (no pel projecte primer). Això va canviar: `order_fabrications.number` ara és una columna senzilla única globalment (abans era `unique(['project_id','number'])`); `Operari\ProjectController` (cerca primer-pel-projecte) es va eliminar i substituir per `Operari\OrderFabricationController@index` (`GET /operari/api/order-fabrications?q=`, retorna files d'OF amb `project` carregat); la forma de resposta de `Operari\EquipmentController@index` va canviar d'un array senzill a `{ order_fabrication, equipment }` perquè `EquipmentList.vue` pugui mostrar la capçalera de projecte/OF que l'usuari va demanar ("ha de sortir el projecte que és"). `ProjectSelector.vue` ara és una única caixa de cerca d'OF, no un selector en dos passos projecte→OF.

Les rutes `/api/*` de la Fase 3 viuen a `routes/api.php` però es carreguen des de `routes/web.php` via `Route::middleware(['auth', EnsureAdminOrQc::class])->prefix('api')->name('api.')->group(base_path('routes/api.php'))` — passen per la pila de middleware `web` basada en sessió, no pel grup `api` sense estat de Laravel, ja que aquí l'autenticació és per sessions, no per tokens. La Fase 4 reflecteix aquest mateix patró per a Operari: `routes/operari-api.php`, carregat de la mateixa manera però amb `EnsureOperari::class` i el prefix `operari/api`.

La Fase 4 també va haver d'ampliar la Fase 3: l'usuari va decidir que l'OF i l'Equipment (números de sèrie) han de ser creats prèviament per Admin/QC, no inventats sobre la marxa per un Operari — així que `Admin\OrderFabricationController` i `Admin\EquipmentController` (+ les seves Store/Update requests) es van afegir a `/api/*` al costat de Project/Section/Question/User. `status`/`checked_at` de l'Equipment mai es poden establir des d'aquest CRUD d'Admin — són gestionats pel sistema, només canvien amb el flux de comprovació de l'Operari (`Operari\EquipmentController@storePhotos`, que calcula `status` segons si l'equipament té defectes / observacions no buides, i després estampa `checked_at`).

Les pujades de fotos fan servir un disc de fitxers dedicat `photos` (`config/filesystems.php`, arrel `storage/app/photos`, `serve => false` ja que són fotos privades de QC, no actius públics) — no el disc `local` per defecte de Laravel, l'arrel del qual és `storage/app/private` en aquesta versió de Laravel, que no coincideix amb el camí `storage/app/photos/` de l'especificació.

Les crides API des de Vue passen per `resources/js/api.js`, un petit embolcall de `fetch()` (no axios — es va evitar afegir-lo com a dependència) que llegeix la cookie `XSRF-TOKEN` i l'adjunta com a `X-XSRF-TOKEN` perquè les peticions POST/PATCH/PUT/DELETE passin la comprovació CSRF de Laravel.

**La navegació és una qüestió de primer ordre que l'usuari ha detectat que faltava dues vegades — tracta-la com un requisit, no com un acabat, a cada pàgina nova.** `/` (`Welcome.vue`) és un selector real (dos botons: login Operari, login Admin/QC), no un simple placeholder estàtic. Cada pàgina autenticada d'Operari (`ProjectSelector`, `EquipmentList`, `FormCheck`) fa servir `Components/OperariNav.vue` (un "← Enrere" contextual + un "Tancar sessió" sempre present); els dos formularis de login tenen un `Link` senzill "← Enrere" cap a `/` (sense tancar sessió allà — encara no estàs autenticat). `Admin/ProjectIndex.vue` té el mateix "← Enrere" cap a `/admin/dashboard`, i el Dashboard hi enllaça cap endavant. Quan afegeixis una pàgina nova a la qual només s'hi arriba fent clic dins l'aplicació, afegeix sempre la manera de tornar-ne a sortir — no esperis que t'ho demanin dues vegades.

**Els botons genèrics d'acció passen per `Components/Button.vue`, no per `<button>` cru amb classes repetides.** L'usuari va detectar que cap botó de l'aplicació tenia efecte visual de "prement" — el primer intent va ser un pedaç global amb `@layer base` a `app.css` afegint `active:scale-95` a l'element `button`, però l'usuari ho va rebutjar explícitament ("vull que segueixis les bones pràctiques, no vull pedaços") i va demanar el component reutilitzable en el seu lloc. `Button.vue` centralitza l'efecte de pressió (`active:scale-95 active:brightness-95`, `hover:brightness-110`, transició) i cinc variants via prop `variant`: `primary` (sòlid fosc, l'acció principal — per defecte), `danger` (sòlid vermell, p. ex. "Desa defecte"), `outline` (amb vora, accions secundàries com els botons compactes "+" d'afegir al catàleg), `ghost` (text gris pla, p. ex. tancar sessió/cancel·lar/editar), `ghost-danger` (text vermell pla, p. ex. "Eliminar"). Es basa en el fallthrough d'atributs per defecte de Vue 3 (un únic `<button>` arrel, sense `inheritAttrs: false`) perquè `class`/`@click`/etc. passats pel pare s'apliquin automàticament a l'arrel — no cal reenviar-los a mà. Tots els botons d'acció genèrica de l'aplicació ja hi passen (logins, `OperariNav`, `FormCheck`, `DefectModal`, `PhotosModal`, i els tres CRUD d'Admin `ProjectIndex`/`SectionIndex`/`QuestionIndex`, incloent-hi els controls compactes ↑/↓/× de reordenació com a `ghost`/`ghost-danger`). **Deliberadament exclosos, no els converteixis:** `ButtonGroup.vue` (els botons Sí/No/Defecte codifiquen un estat seleccionat amb colors verd/gris/vermell lligats al valor de la resposta, no una acció genèrica), `ChartDefects.vue` (el seu botó de toggle fa servir CSS amb àmbit i variables de tema clar/fosc pròpies de l'skill `dataviz`; forçar-lo a `Button` li faria perdre la consciència de tema fosc) i `LanguageSelector.vue` (el mateix patró de control segmentat seleccionat/no-seleccionat que `ButtonGroup`, no un botó d'acció).

El dashboard de la Fase 5 segueix el mètode de l'skill `dataviz`: els gràfics de barres de categoria nominal (defectes per tipo, per responsabilitat, taxa de defectes per secció) són de sèrie única, així que cada barra fa servir el mateix blau categòric de l'slot 1 (`#2a78d6` clar / `#3987e5` fosc) — segons aquest skill, un gràfic nominal d'una sola sèrie mai necessita la rotació categòrica multi-to, que es reserva per a sèries genuïnament diferents i superposades. `ChartDefects.vue` és un únic component de barres genèric i reutilitzable (props: `title`, `items`) que alimenta els tres gràfics, cadascun amb un toggle de vista-taula per accessibilitat. `Admin\PhotoController@show` serveix una foto a través del disc privat `photos` i retorna 404 (no un 500) si el fitxer no existeix al disc — detectat en un test de navegador real on les files `Photo` de demostració sembrades no tenien fitxer al darrere.

Disseny d'autenticació implementat realment a la Fase 2 (com a referència, ja que matisa el que la llista de fases més avall només esbossa): un únic guard `web` contra la taula `users` serveix els dos formularis de login — Admin/QC via `email`, Operari via `username` — perquè un usuari mai té els dos camps alhora, així que `Auth::attempt()` no es pot encreuar de manera natural; cada controller igualment torna a comprovar el `role` després d'un intent reeixit, com a defensa addicional. Els convidats es redirigeixen al formulari de login que correspon a l'àrea a la qual han accedit (`operari.login` per a `/operari*`, `login` en la resta de casos) via `redirectGuestsTo` a `bootstrap/app.php`.

## Visió general del projecte

AH_ManufactQC és un sistema digital de control de qualitat per a inspeccions de fabricació. Els Operaris responen qüestionaris per número de sèrie, registren defectes amb fotos; QC/Admin gestionen les preguntes del qüestionari i revisen dashboards/estadístiques.

Rols:
- **Operari** — frontend mòbil/tablet. Inicia sessió amb usuari+contrasenya. Respon qüestionaris, marca defectes, puja 5-6 fotos opcionals.
- **Responsable QC** — backend web. Crea preguntes, veu el dashboard, revisa defectes/estadístiques.
- **Administrador** — backend web. CRUD complet, gestiona usuaris i configuració.

Flux principal: login Operari → selecciona projecte/OF/número de sèrie (entrat manualment, no seqüencial) → respon preguntes de QUALITAT (Sí/No/Defecte) → pop-up de defecte (tipus, observació, responsabilitat, accions) si cal, es permeten múltiples defectes per sèrie → observacions de text lliure (opcional) → pop-up de fotos (5-6 opcionals) → en desar s'estableix `checked_at = NOW()` → es mostra el llistat de números de sèrie amb colors (verd/vermell/taronja).

## Regla d'or: sempre preguntar abans d'executar

Mai implementis ni executis codi sense aprovació explícita. Procediment per a cada unitat de feina:
1. Explica què es construirà.
2. Espera l'aprovació explícita (p. ex. "Sí, crea...", "Implementa...").
3. Implementa-ho i escriu tests de verificació alhora.
4. Confirma que els tests passen abans de donar la feina per acabada.

## Stack tecnològic

- Backend: **Laravel 13.x** + Inertia.js (adaptador Inertia Laravel v3) — nota: l'especificació original del projecte deia Laravel 11, però la 13.x és l'estable actual i es va triar deliberadament en muntar l'estructura; continua fent servir la 13.x d'ara endavant.
- Frontend: Vue 3 + Vite + **Tailwind CSS v3** (no v4 — es va rebaixar deliberadament per estabilitat; la configuració viu a `tailwind.config.js` + `postcss.config.js`, directives clàssiques `@tailwind base/components/utilities` a `resources/css/app.css`)
- Base de dades: SQLite (`database/database.sqlite`, `DB_CONNECTION=sqlite` ja establert a `.env`)
- i18n: vue-i18n (Català + Castellà) — tot el text visible per l'usuari passa per `$t('key')`, amb les claus definides a `resources/lang/ca.json` i `resources/lang/es.json`
- Icones: `@lucide/vue` (l'especificació anomenava `lucide-vue-next`, que està obsolet a favor d'aquest paquet — es fa servir el que sí que es manté)
- Testing: Pest 4 (backend, `./vendor/bin/pest`), Vitest (frontend) — cada funcionalitat es lliura amb tests, no és opcional
- Auth: sessions de Laravel (no JWT), multi-rol — Admin/QC inicien sessió amb email, Operari amb usuari
- Emmagatzematge de fitxers: disc local a `storage/app/photos/`, no S3

## Convencions de codi

**PHP**
- Variables: `camelCase`
- Classes: `PascalCase`
- Constants: `SCREAMING_SNAKE_CASE`
- Tota la validació de peticions passa per Form Requests — mai validar en línia dins d'un controller
- Els models fan servir relacions Eloquent (no consultes en cru) per a les associacions

**Vue/JavaScript**
- Components: noms de fitxer en `PascalCase`
- Variables i mètodes: `camelCase`
- Tot el text visible via `$t('key')`, mai cadenes de text fixades al codi

## Estructura de carpetes obligatòria

```
app/Models/                    Models Eloquent
app/Http/Controllers/          Subcarpetes Admin/ i Operari/
app/Http/Middleware/           Subcarpetes Admin/ i Operari/ per al middleware de guarda de rol (p. ex.
                                EnsureAdminOrQc / EnsureOperari de la Fase 2); NO "Web/" — col·lisiona
                                amb el nom del grup de middleware natiu de Laravel "web", que és una
                                cosa diferent. El HandleInertiaRequests propi d'Inertia es queda
                                directament sota Middleware/ ja que és global, no específic d'un rol
app/Http/Requests/             Form Requests (tota la validació)
database/migrations/
database/factories/
database/seeders/
resources/js/Pages/            Pàgines Inertia dins Operari/ i Admin/
resources/js/Components/       Components Vue reutilitzables
resources/js/__tests__/        Specs de Vitest, reflecteix l'estructura de Pages/Components
resources/lang/                ca.json, es.json
tests/Feature/                 Tests Feature de Pest
storage/app/photos/            Fotos d'equipaments pujades
```

## Conceptes de domini

| Concepte | Definició |
|---------|-----------|
| Project | Número (p. ex. 1400C0000.00), una única Família (DB2, DB3, catàleg extensible), un conjunt ordenat de Seccions que alhora formen la seva descripció combinada ("AH17DX2 TS USB"), observacions globals |
| OF (OrderFabrication) | Ordre de fabricació, agrupa números de sèrie |
| Equipment | Número de sèrie, entrat manualment (no seqüencial), pertany a un Project + OF |
| Section | Alhora una paraula de la descripció d'un projecte *i* propietària d'un qüestionari (p. ex. "AH17DX2", o la genèrica "QUALITAT") — un catàleg extensible, triat per projecte amb un ordre explícit |
| Question | Una pregunta Sí/No/Defecte que pertany a una Section, etiquetada amb una `category` (Estètica / Funcional-Mecànica / Electrònica) |
| Defect | tipo + observació + responsabilitat + accions, lligat a un Equipment |
| Photo | Fins a 5-6 fotos opcionals per Equipment, preses quan es marca com a OK |
| checked_at | Marca de temps establerta quan un Equipment es marca com a OK |

## Fases d'implementació

La feina avança estrictament en aquest ordre; cada fase necessita els seus propis tests passant i el vist-i-plau explícit de l'usuari abans de començar la següent.

1. **Setup Inicial** — FET. Models, migracions, factories, seeders (User, Project, Section, Question, OrderFabrication, Equipment, Answer, Defect, Photo), tests base de Pest (`DatabaseSeederTest`, `ModelRelationshipsTest`).
2. **Autenticació multi-rol** — FET. Middleware `Admin\EnsureAdminOrQc` + `Operari\EnsureOperari`, `AuthController` (Admin/QC, email) + `Operari\LoginController` (usuari), `LoginRequest`/`OperariLoginRequest`, `AuthenticationTest`, `AuthorizationTest`. També es van crear pàgines placeholder bàsiques `Auth/Login.vue`, `Operari/Login.vue`, `Admin/Dashboard.vue`, `Operari/ProjectSelector.vue` — funcionals però sense estil ni i18n, ja que la interfície real és àmbit de la Fase 4/5; no et sorprenguis que ja existeixin en començar aquelles fases, només cal estilitzar-les i connectar-les bé en lloc de tornar-les a crear.
3. **Backend CRUD (Admin/QC)** — FET. `Admin\{Project,Section,Question,User}Controller` (index/store/show/update/destroy, JSON), parella de Form Request Store+Update per als quatre recursos (no només Project — es va ampliar més enllà de l'especificació original per coherència, vegeu la nota de la "Regla d'or" més amunt), rutes `/api/*` (vegeu la nota d'estat més amunt), un fitxer de test de Pest per controller. `UserController` posa a `null` l'`email` per a operari i el `username` per a admin/qc, i confia en el cast `'hashed'` de `User` per xifrar contrasenyes (no cal `Hash::make()` manual — detecta sol els valors ja xifrats via `Hash::isHashed()`).
4. **Frontend Operari** — FET. `Operari/{Login,ProjectSelector,FormCheck,DefectModal,PhotosModal,EquipmentList}.vue`, `Components/{LanguageSelector,FormField,ButtonGroup}.vue`, `resources/lang/{ca,es}.json` + `resources/js/i18n.js` (vue-i18n, idioma persistit a `localStorage` sota la clau `ah_manufactqc_locale`), helper `resources/js/api.js`, ampliacions de backend descrites a la nota d'estat més amunt, specs de Vitest per a `Login`, `FormCheck`, `LanguageSelector`. Navegació: `/operari` (ProjectSelector) → `/operari/order-fabrications/{of}/equipment-list` (EquipmentList, fa doble funció de selector de sèrie i de resum final acolorit) → `/operari/equipment/{equipment}/check` (FormCheck, incrusta DefectModal/PhotosModal) → torna a EquipmentList en acabar.
5. **Dashboard QC + i18n complet** — FET. `Admin\DashboardController@index` (un sol endpoint, `stats`/`defects_by_type`/`responsibilities`/`trends`/`recent_photos`, filtrable per `project_id`/`from`/`to`), `Admin\PhotoController@show` (serveix un fitxer de foto, 404 si no existeix), `Admin/Dashboard.vue` + components `StatCard`/`ChartDefects`/`PhotoGrid`/`FilterBar` (compleixen l'skill de dataviz, vegeu la nota d'estat més amunt), claus i18n `dashboard.*` en tots dos idiomes més un test automàtic de paritat de claus ca/es (`i18n-parity.spec.js`), `DashboardControllerTest`, `PhotoControllerTest`, `Dashboard.spec.js`.

## Comandes

```bash
# Servidors
php artisan serve                    # Backend, port 8000
npm run dev                          # Frontend Vite

# Base de dades
php artisan migrate
php artisan migrate:fresh --seed
php artisan tinker

# Tests
./vendor/bin/pest                    # Tots els tests de Pest
./vendor/bin/pest tests/Feature      # Només els Feature tests
./vendor/bin/pest --filter=some_test_name   # Un sol test
npx vitest run                       # Tots els tests de Vitest, un cop
npx vitest                           # Vitest en mode watch

# Formatació
./vendor/bin/pint                    # PHP

# Build de producció
npm run build
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```
