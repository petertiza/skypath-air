# SkyPath AIR - Rezervačný systém leteniek

**Moderný rezervačný systém** leteniek s kompletným admin panelom, check-inom a vernostným programom.

## Hlavné funkcie

### Pre zákazníkov
| Funkcia | Popis |
|---------|-------|
| **Interaktívna mapa** | MapLibre GL s route lines a plane markermi |
| **Výber sedadiel** | Podľa konfigurácie lietadla (B738, E190) |
| **Platobný formulár** | Luhn validácia + 15min timer |
| **Online check-in** | QR kód + PDF palubný lístok (Dompdf) |
| **Vernostný systém** | Body za lety, zľavy pri platbe |
| **Multi-pasažier** | Možnosť zakúpenia letenky pre viacerých cestujúcich |

### Admin panel
| Modul | Funkcie |
|-------|---------|
| **Dashboard** | Revenue, obsadenosť, štatistiky realtime |
| **Lety & lietadlá** | CRUD + konfigurácia sedadiel |
| **Používatelia** | Registrácia, body, role (admin/user) |
| **Rezervácie** | Prehľad, zrušenie, check-in stav |
| **Reporty** | Analýzy výkonu a príjmov |

### Frontend: Vue 3 + Vite
**Vue Router**
**MapLibre GL (interaktívna mapa)**
**Dark/Light mode prepínanie**

### Backend: PHP 8 + MySQL
**Endroid QR Code**
**Dompdf (PDF palubný lístok)**
**PHPMailer (email notifikácie)**

### Databáza: MySQL
