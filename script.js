// ============================================================
// XHR Helper — withCredentials: true per mantenere la sessione
// ============================================================
function makeXHRRequest(method, url, data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.withCredentials = true; // CRITICO: invia il cookie di sessione PHP
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (e) {
                        resolve(xhr.responseText);
                    }
                } else {
                    try {
                        reject(JSON.parse(xhr.responseText));
                    } catch (e) {
                        reject({ error: 'Errore di comunicazione' });
                    }
                }
            }
        };

        xhr.onerror = function () {
            reject({ error: 'Errore di rete' });
        };

        xhr.send(data ? JSON.stringify(data) : null);
    });
}

// ============================================================
// Caricamento ristoranti
// ============================================================
async function loadRestaurants() {
    const container = document.getElementById('restaurants-list');
    if (!container) return;

    container.innerHTML = '<p style="color:var(--stone); font-style:italic;">Caricamento...</p>';

    try {
        const restaurants = await makeXHRRequest('GET', 'api.php?endpoint=restaurants');
        displayRestaurants(restaurants);
    } catch (error) {
        container.innerHTML = '<div class="state-empty"><p>Errore nel caricamento dei ristoranti.</p></div>';
    }
}

function displayRestaurants(restaurants) {
    const container = document.getElementById('restaurants-list');
    if (!container) return;

    if (!restaurants || restaurants.length === 0) {
        container.innerHTML = '<div class="state-empty"><p>Nessun ristorante presente.</p></div>';
        return;
    }

    container.innerHTML = restaurants.map(r => `
        <div class="restaurant-card" onclick="showRestaurantDetails(${r._id})">
            <img class="card-image" src="${r.image_url || 'https://placehold.co/600x400/F0E3D3/B8B7A3?text=+'}" alt="${escapeHtml(r.name)}">
            <div class="restaurant-info">
                <h3>${escapeHtml(r.name)}</h3>
                <div class="meta">
                    <span class="address">${escapeHtml(r.address)}</span>
                    <span class="price">${'€'.repeat(r.price_range)}</span>
                </div>
                <p class="description">${escapeHtml(r.description)}</p>
                <div class="card-footer">
                    <button class="btn-primary" onclick="event.stopPropagation(); showRestaurantDetails(${r._id})">Scopri</button>
                </div>
            </div>
        </div>
    `).join('');
}

// ============================================================
// Dettaglio ristorante
// ============================================================
async function showRestaurantDetails(restaurantId) {
    try {
        const restaurant = await makeXHRRequest('GET', `api.php?endpoint=restaurants&id=${restaurantId}`);

        // Rileva login dalla presenza del .nav-pill (span con nome utente)
        const isLoggedIn = document.querySelector('.nav-pill') !== null;

        const priceLabel = ['', 'Economico', 'Moderato', 'Costoso', 'Lusso'][restaurant.price_range] || '';
        const priceSymbol = '€'.repeat(restaurant.price_range);

        const reviewsHtml = restaurant.reviews && restaurant.reviews.length > 0
            ? restaurant.reviews.map(review => {
                let dateStr = '';
                try {
                    const ts = review.created_at?.$date?.$numberLong
                        ? parseInt(review.created_at.$date.$numberLong)
                        : review.created_at?.$date || review.created_at;
                    dateStr = new Date(ts).toLocaleDateString('it-IT');
                } catch (e) { dateStr = ''; }

                return `
                    <div class="review">
                        <div class="review-header">
                            <span class="review-author">${escapeHtml(review.user_name)}</span>
                            <span class="review-date">${dateStr}</span>
                        </div>
                        <p>${escapeHtml(review.text)}</p>
                    </div>`;
            }).join('')
            : '<p class="reviews-empty">Nessuna recensione. Sii il primo a condividere la tua esperienza.</p>';

        const formHtml = isLoggedIn
            ? `<div class="review-form-wrapper">
                <p class="review-form-title">La tua recensione</p>
                <textarea id="review-text" rows="3" placeholder="Condividi la tua esperienza..."></textarea>
                <button onclick="submitReviewInline(${restaurant._id})">Pubblica</button>
               </div>`
            : `<div class="review-login-prompt">
                <a href="login.php">Accedi</a> per scrivere una recensione.
               </div>`;

        // Rimuovi eventuali modali precedenti
        document.querySelectorAll('.modal').forEach(m => m.remove());

        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content modal-large">
                <button class="modal-close" onclick="this.closest('.modal').remove()" aria-label="Chiudi">&times;</button>

                <h2>${escapeHtml(restaurant.name)}</h2>

                <img class="modal-hero-img" src="${restaurant.image_url || 'https://placehold.co/820x260/F0E3D3/B8B7A3?text=+'}" alt="${escapeHtml(restaurant.name)}">

                <div class="modal-meta">
                    <div class="modal-meta-item">
                        <span class="modal-meta-label">Indirizzo</span>
                        <span class="modal-meta-value">${escapeHtml(restaurant.address)}</span>
                    </div>
                    <div class="modal-meta-item">
                        <span class="modal-meta-label">Fascia di prezzo</span>
                        <span class="modal-meta-value price-value">${priceSymbol} &nbsp;${priceLabel}</span>
                    </div>
                </div>

                <p class="modal-description">${escapeHtml(restaurant.description)}</p>

                <div class="section-divider"></div>

                ${formHtml}

                <p class="reviews-section-title">Recensioni (${restaurant.reviews?.length || 0})</p>
                <div class="reviews-list">${reviewsHtml}</div>
            </div>
        `;

        document.body.appendChild(modal);

        // Chiudi cliccando fuori
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.remove();
        });

    } catch (error) {
        alert('Errore: ' + (error.error || 'Impossibile caricare il ristorante.'));
    }
}

// ============================================================
// Invio recensione
// ============================================================
async function submitReviewInline(restaurantId) {
    const textarea = document.querySelector('#review-text');
    if (!textarea) return;

    const reviewText = textarea.value.trim();
    if (!reviewText) {
        alert('Scrivi una recensione prima di inviare.');
        return;
    }

    try {
        const response = await makeXHRRequest('POST', 'api.php?endpoint=reviews', {
            restaurant_id: parseInt(restaurantId),
            text: reviewText
        });

        if (response.success) {
            // Ricarica il modale aggiornato
            document.querySelectorAll('.modal').forEach(m => m.remove());
            showRestaurantDetails(restaurantId);
        } else {
            alert('Errore: ' + (response.error || 'Impossibile inviare la recensione.'));
        }
    } catch (error) {
        if (error.error && (error.error.includes('loggato') || error.error.includes('autenticato'))) {
            alert('Devi essere loggato per recensire. Verrai reindirizzato alla pagina di login.');
            window.location.href = 'login.php';
        } else {
            alert('Errore: ' + (error.error || 'Impossibile inviare la recensione.'));
        }
    }
}

// ============================================================
// Login & Registrazione
// ============================================================
async function login() {
    const email    = document.getElementById('login-email')?.value?.trim();
    const password = document.getElementById('login-password')?.value;

    if (!email || !password) {
        alert('Inserisci email e password.');
        return;
    }

    try {
        const res = await makeXHRRequest('POST', 'api.php?endpoint=login', { email, password });
        if (res.success) window.location.href = 'index.php';
    } catch (error) {
        alert('Credenziali non valide.');
    }
}

async function register() {
    const name     = document.getElementById('reg-name')?.value?.trim();
    const email    = document.getElementById('reg-email')?.value?.trim();
    const password = document.getElementById('reg-password')?.value;

    if (!name || !email || !password || password.length < 6) {
        alert('Compila tutti i campi. La password deve contenere almeno 6 caratteri.');
        return;
    }

    try {
        await makeXHRRequest('POST', 'api.php?endpoint=register', { name, email, password });
        alert('Registrazione completata. Ora puoi accedere.');
        showTab('login');
        const emailInput = document.getElementById('login-email');
        if (emailInput) emailInput.value = email;
    } catch (error) {
        alert('Errore: ' + (error.error || 'Email già in uso.'));
    }
}

// ============================================================
// Gestione ristoranti (admin)
// ============================================================
function showAddRestaurantForm() {
    document.getElementById('add-restaurant-modal').style.display = 'flex';
}

function closeAddRestaurantModal() {
    document.getElementById('add-restaurant-modal').style.display = 'none';
}

async function addRestaurant() {
    const data = {
        name:        document.getElementById('rest-name')?.value?.trim(),
        address:     document.getElementById('rest-address')?.value?.trim(),
        price_range: parseInt(document.getElementById('rest-price')?.value),
        image_url:   document.getElementById('rest-image')?.value?.trim() || 'https://placehold.co/600x400/F0E3D3/B8B7A3?text=+',
        description: document.getElementById('rest-desc')?.value?.trim()
    };

    if (!data.name || !data.address || !data.description) {
        alert('Compila tutti i campi obbligatori.');
        return;
    }

    try {
        await makeXHRRequest('POST', 'api.php?endpoint=restaurants', data);
        alert('Ristorante aggiunto con successo.');
        closeAddRestaurantModal();
        loadRestaurants();
    } catch (error) {
        alert('Errore: ' + (error.error || 'Permessi insufficienti.'));
    }
}

async function showMyRestaurants() {
    try {
        const restaurants = await makeXHRRequest('GET', 'api.php?endpoint=my-restaurants');
        displayRestaurants(restaurants);
    } catch (error) {
        alert('Errore: ' + (error.error || 'Impossibile caricare i ristoranti.'));
    }
}

// ============================================================
// Tab Login/Registrazione
// ============================================================
function showTab(tab) {
    const loginTab    = document.getElementById('login-tab');
    const registerTab = document.getElementById('register-tab');
    const btns        = document.querySelectorAll('.tab-btn');

    if (tab === 'login') {
        loginTab.style.display    = 'flex';
        registerTab.style.display = 'none';
        btns[0]?.classList.add('active');
        btns[1]?.classList.remove('active');
    } else {
        loginTab.style.display    = 'none';
        registerTab.style.display = 'flex';
        btns[0]?.classList.remove('active');
        btns[1]?.classList.add('active');
    }
}

// ============================================================
// Utility
// ============================================================
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}


