document.addEventListener('DOMContentLoaded', async () => {
    const profileForm = document.getElementById('profileForm');
    const message = document.getElementById('message');
    const userNameSpan = document.getElementById('userName');
    const userLoginSpan = document.getElementById('userLogin');
    const loading = document.getElementById('loading');

    async function loadProfile() {
        try {
            const response = await fetch('/web4/lab8/api/me.php');
            const data = await response.json();

            if (response.ok && data.success) {
                const user = data.data;
                document.getElementById('name').value = user.name;
                document.getElementById('phone').value = user.phone;
                document.getElementById('email').value = user.email;
                userNameSpan.textContent = user.name;
                userLoginSpan.textContent = user.login;
                loading.style.display = 'none';
                profileForm.style.display = 'block';
            } else if (response.status === 401) {
                window.location.href = '/web4/lab8/public/login.html';
            } else {
                window.location.href = '/web4/lab8/public/login.html';
            }
        } catch (error) {
            window.location.href = '/web4/lab8/public/login.html';
        }
    }

    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                name: document.getElementById('name').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value
            };

            message.className = 'message';
            message.style.display = 'none';

            try {
                const response = await fetch('/web4/lab8/api/me.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    message.textContent = '✅ Данные успешно обновлены!';
                    message.classList.add('success');
                    message.style.display = 'block';
                    userNameSpan.textContent = formData.name;

                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 3000);
                } else if (response.status === 401) {
                    window.location.href = '/web4/lab8/public/login.html';
                } else {
                    const errors = data.errors;
                    let errorText = '❌ Ошибка:\n';
                    for (let key in errors) {
                        errorText += `- ${errors[key]}\n`;
                    }
                    message.textContent = errorText;
                    message.classList.add('error');
                    message.style.display = 'block';
                }
            } catch (error) {
                message.textContent = '❌ Ошибка соединения. Попробуйте позже.';
                message.classList.add('error');
                message.style.display = 'block';
            }
        });
    }

    loadProfile();
});