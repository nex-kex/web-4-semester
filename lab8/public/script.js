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

// FORM SUBMISSION - отправка на API обратной связи
const contactForm = document.getElementById('contactForm');
const formMessage = document.getElementById('formMessage');

if (contactForm) {
    contactForm.addEventListener('submit', handleFormSubmit);
}

async function handleFormSubmit(e) {
    e.preventDefault();

    const submitBtn = contactForm.querySelector('.submit-btn');
    const formData = new FormData(contactForm);

    // Собираем данные
    const jsonData = {
        name: formData.get('name'),
        phone: formData.get('phone'),
        email: formData.get('email'),
        comment: formData.get('comment') || ''
    };

    // Блокируем кнопку и показываем индикатор
    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправка...';
    formMessage.className = 'form-message';
    formMessage.style.display = 'none';

    try {
        const response = await fetch('/web4/lab8/api/feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(jsonData)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Успех - показываем сообщение
            formMessage.textContent = '✅ ' + data.message;
            formMessage.classList.add('success');
            formMessage.style.display = 'block';
            contactForm.reset();

            // Скрываем сообщение через 5 секунд
            setTimeout(() => {
                formMessage.style.display = 'none';
            }, 5000);
        } else {
            // Ошибка валидации
            const errors = data.errors;
            let errorText = '❌ Пожалуйста, исправьте ошибки:\n';
            for (let key in errors) {
                errorText += `- ${errors[key]}\n`;
            }
            formMessage.textContent = errorText;
            formMessage.classList.add('error');
            formMessage.style.display = 'block';
        }
    } catch (error) {
        console.error('Fetch error:', error);
        formMessage.textContent = '❌ Ошибка соединения. Пожалуйста, попробуйте позже или позвоните нам.';
        formMessage.classList.add('error');
        formMessage.style.display = 'block';
    } finally {
        // Разблокируем кнопку
        submitBtn.disabled = false;
        submitBtn.textContent = 'Отправить';
    }
}

// FAQ ACCORDION
const faqItems = document.querySelectorAll('.faq-item');

if (faqItems.length > 0) {
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        if (question) {
            question.addEventListener('click', () => {
                // Закрываем все остальные
                faqItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains('active')) {
                        otherItem.classList.remove('active');
                    }
                });
                // Переключаем текущий
                item.classList.toggle('active');
            });
        }
    });
}