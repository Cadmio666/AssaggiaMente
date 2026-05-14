# AssaggiaMente - Blog Recensioni Ristoranti

## Requisiti Tecnici Implementati

### 1. Comunicazione Client-Server con XHR
- **Cos'è XHR**: XMLHttpRequest è un'API JavaScript che permette di effettuare richieste HTTP asincrone
- **Ciclo di vita**: 
  1. Creazione (new XMLHttpRequest())
  2. Apertura (open())
  3. Invio (send())
  4. Ricezione (onreadystatechange)
  5. ReadyState 4 = completato
- **Differenze con Fetch**: XHR ha API più complessa ma supporto nativo eventi progress, Fetch è più moderna ma richiede polyfill per IE

### 2. Database NoSQL (MongoDB)
**Scelta motivata**: Documenti flessibili per ristoranti con struttura variabile
**Differenze SQL vs NoSQL**:
- SQL: schemi rigidi, relazioni, JOIN
- NoSQL: schemi flessibili, documenti embedded, performance scalabilità orizzontale

**Vantaggi nel progetto**:
- Recensioni annidate facilmente
- Nessuna migrazione per nuovi campi
- Performance elevate per letture/scritture

### 3. API REST
| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| GET | /api.php?endpoint=restaurants | Lista ristoranti |
| GET | /api.php?endpoint=restaurants&id=X | Dettaglio ristorante |
| POST | /api.php?endpoint=register | Registrazione utente |
| POST | /api.php?endpoint=login | Login utente |
| POST | /api.php?endpoint=restaurants | Crea ristorante (admin) |
| POST | /api.php?endpoint=reviews | Crea recensione |
| PUT | /api.php?endpoint=restaurants&id=X | Modifica ristorante |
| DELETE | /api.php?endpoint=restaurants&id=X | Elimina ristorante |
| DELETE | /api.php?endpoint=reviews&id=X | Elimina recensione |

### 4. GitHub
Repository organizzata con commit semantici e branch protection

## Installazione

```bash
# 1. Installare MongoDB
sudo apt-get install mongodb

# 2. Installare PHP MongoDB driver
composer require mongodb/mongodb

# 3. Configurare virtual host
# 4. Importare dati di test (opzionale)