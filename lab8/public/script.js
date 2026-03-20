// BURGER MENU
const burgerMenu = document.getElementById('burgerMenu');
const nav = document.getElementById('nav');

burgerMenu.addEventListener('click', () => {
    burgerMenu.classList.toggle('active');
    nav.classList.toggle('active');
});

// DROPDOWN MENU (мобильные)
const dropdowns = document.querySelectorAll('.dropdown');
nav.addEventListener('click', function(e) {
    const target = e.target;
    const dropdownLink = target.closest('.dropdown .nav-link');
    if (dropdownLink && window.innerWidth < 1024) {
        const dropdown = dropdownLink.closest('.dropdown');
        if (dropdown) {
            e.preventDefault();
            dropdown.classList.toggle('active');
        }
        return;
    }
    if (target.classList.contains('nav-link') && !target.closest('.dropdown') && window.innerWidth < 1024) {
        burgerMenu.classList.remove('active');
        nav.classList.remove('active');
        dropdowns.forEach(d => d.classList.remove('active'));
    }
    if (target.classList.contains('dropdown-link') && window.innerWidth < 1024) {
        burgerMenu.classList.remove('active');
        nav.classList.remove('active');
        dropdowns.forEach(d => d.classList.remove('active'));
    }
});

// REVIEWS CAROUSEL (без авто-прокрутки)
let currentReview = 0;
const reviews = document.querySelectorAll('.review');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

function showReview(index) {
    reviews.forEach((review, i) => {
        review.classList.toggle('active', i === index);
    });
}

function nextReview() {
    currentReview = (currentReview + 1) % reviews.length;
    showReview(currentReview);
}

function prevReview() {
    currentReview = (currentReview - 1 + reviews.length) % reviews.length;
    showReview(currentReview);
}

if (nextBtn) nextBtn.addEventListener('click', nextReview);
if (prevBtn) prevBtn.addEventListener('click', prevReview);
if (reviews.length > 0) showReview(0);

// TEAM CAROUSEL (4 карточки на экране, прокрутка по одной)
const teamContainer = document.getElementById('teamContainer');
const teamMembers = document.querySelectorAll('.team-member');
const teamPrevBtn = document.getElementById('teamPrevBtn');
const teamNextBtn = document.getElementById('teamNextBtn');

let currentTeamIndex = 0;
let cardsToShow = 4; // Количество видимых карточек
let cardWidth = 0;
const cardGap = 20; // Должен соответствовать gap в CSS

function updateCardsToShow() {
    if (window.innerWidth <= 480) {
        cardsToShow = 1;
    } else if (window.innerWidth <= 768) {
        cardsToShow = 2;
    } else if (window.innerWidth <= 992) {
        cardsToShow = 3;
    } else {
        cardsToShow = 4;
    }
    updateCarousel();
}

function updateCarousel() {
    if (!teamMembers.length) return;

    // Рассчитываем ширину карточки с учетом gap
    const containerWidth = teamContainer.parentElement.offsetWidth;
    const totalGapWidth = cardGap * (cardsToShow - 1);
    cardWidth = (containerWidth - totalGapWidth) / cardsToShow;

    // Устанавливаем ширину для каждой карточки
    teamMembers.forEach(member => {
        member.style.flex = `0 0 ${cardWidth}px`;
        member.style.maxWidth = `${cardWidth}px`;
    });

    // Проверяем границы
    const maxIndex = Math.max(0, teamMembers.length - cardsToShow);
    if (currentTeamIndex > maxIndex) {
        currentTeamIndex = maxIndex;
    }

    // Обновляем трансформацию
    const offset = currentTeamIndex * (cardWidth + cardGap);
    teamContainer.style.transform = `translateX(-${offset}px)`;

    // Обновляем состояние кнопок
    if (teamPrevBtn) {
        teamPrevBtn.disabled = currentTeamIndex <= 0;
    }
    if (teamNextBtn) {
        teamNextBtn.disabled = currentTeamIndex >= maxIndex;
    }
}

function nextTeam() {
    const maxIndex = teamMembers.length - cardsToShow;
    if (currentTeamIndex < maxIndex) {
        currentTeamIndex++;
        updateCarousel();
    }
}

function prevTeam() {
    if (currentTeamIndex > 0) {
        currentTeamIndex--;
        updateCarousel();
    }
}

// Обработчики событий
if (teamNextBtn) teamNextBtn.addEventListener('click', nextTeam);
if (teamPrevBtn) teamPrevBtn.addEventListener('click', prevTeam);

// Обновляем при изменении размера окна
window.addEventListener('resize', () => {
    updateCardsToShow();
    updateCarousel();
});

// Инициализация
updateCardsToShow();
updateCarousel();

// PRICING CARDS (кнопка на мобильных)
const pricingCards = document.querySelectorAll('.pricing-card');
pricingCards.forEach(card => {
    card.addEventListener('click', (e) => {
        if (window.innerWidth < 768 && !e.target.closest('.pricing-btn')) {
            pricingCards.forEach(otherCard => {
                otherCard.classList.toggle('active', otherCard === card);
            });
        }
    });
});
document.addEventListener('click', (e) => {
    if (window.innerWidth < 768 && !e.target.closest('.pricing-card')) {
        pricingCards.forEach(card => card.classList.remove('active'));
    }
});

// FORM SUBMISSION
const contactForm = document.getElementById('contactForm');
const formMessage = document.getElementById('formMessage');
const FORMCARRY_ENDPOINT = 'https://formcarry.com/s/GeLrFCro_b1';

contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = contactForm.querySelector('.submit-btn');
    const formData = new FormData(contactForm);
    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправка...';
    formMessage.classList.remove('success', 'error');
    formMessage.style.display = 'none';
    try {
        const response = await fetch(FORMCARRY_ENDPOINT, {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (response.ok) {
            formMessage.textContent = 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами.';
            formMessage.classList.add('success');
            formMessage.style.display = 'block';
            contactForm.reset();
        } else {
            throw new Error(data.message || 'Ошибка');
        }
    } catch (error) {
        formMessage.textContent = 'Ошибка. Попробуйте позже или позвоните нам.';
        formMessage.classList.add('error');
        formMessage.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Отправить';
    }
});

// SMOOTH SCROLL
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '' && href !== 'javascript:void(0)') {
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
            }
        }
    });
});

// FAQ ACCORDION
const faqItems = document.querySelectorAll('.faq-item');
faqItems.forEach(item => {
    item.querySelector('.faq-question').addEventListener('click', () => {
        const isActive = item.classList.contains('active');
        faqItems.forEach(otherItem => otherItem.classList.remove('active'));
        if (!isActive) item.classList.add('active');
    });
});

// FORM SUBMISSION with REST API
const contactForm = document.getElementById('contactForm');
const formMessage = document.getElementById('formMessage');

const supportsFetch = window.fetch && window.Promise && window.JSON;

if (supportsFetch) {
    contactForm.addEventListener('submit', handleFormSubmit);
}

async function handleFormSubmit(e) {
    e.preventDefault();

    const submitBtn = contactForm.querySelector('.submit-btn');
    const formData = new FormData(contactForm);

    const jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });

    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправка...';
    formMessage.classList.remove('success', 'error');
    formMessage.style.display = 'none';

    try {
        const response = await fetch('/lab7/api/users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(jsonData)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            formMessage.innerHTML = `✅ Спасибо! Ваша заявка отправлена.<br>
                <strong>Логин:</strong> ${data.data.login}<br>
                <strong>Пароль:</strong> ${data.data.password}<br>
                <small>Сохраните эти данные для возможности редактирования заявки</small>`;
            formMessage.classList.add('success');
            formMessage.style.display = 'block';
            contactForm.reset();

            localStorage.setItem('gym_user_id', data.data.id);
            localStorage.setItem('gym_user_login', data.data.login);
        } else {
            throw new Error(data.error || 'Ошибка сервера');
        }
    } catch (error) {
        formMessage.textContent = '❌ Ошибка: ' + error.message;
        formMessage.classList.add('error');
        formMessage.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Отправить';
    }
}

// Fallback для браузеров без fetch
if (!supportsFetch) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'form_submit';
    input.value = '1';
    contactForm.appendChild(input);
    contactForm.action = '/lab7/api/users';
    contactForm.method = 'POST';
}