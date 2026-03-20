document.addEventListener('DOMContentLoaded', async () => {
    // Проверяем, есть ли сессия или сохраненные данные
    const checkAuth = async () => {
        try {
            const response = await fetch('/web4/lab8/api/me.php');

            if (response.status === 401) {
                // Не авторизован - перенаправляем на страницу входа
                window.location.href = '/web4/lab8/public/login.html';
                return false;
            }

            const data = await response.json();
            if (data.success) {
                return data.data;
            } else {
                window.location.href = '/web4/lab8/public/login.html';
                return false;
            }
        } catch (error) {
            window.location.href = '/web4/lab8/public/login.html';
            return false;
        }
    };

    const user = await checkAuth();
    if (!user) return;

    // Заполняем форму данными пользователя
    document.getElementById('name').value = user.name;
    document.getElementById('phone').value = user.phone;
    document.getElementById('email').value = user.email;
    document.getElementById('comment').value = user.comment || '';
    document.getElementById('userName').textContent = user.name;
    document.getElementById('userLogin').textContent = user.login;

    // Обработка отправки формы
    const profileForm = document.getElementById('profileForm');
    const message = document.getElementById('message');

    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            name: document.getElementById('name').value,
            phone: document.getElementById('phone').value,
            email: document.getElementById('email').value,
            comment: document.getElementById('comment').value
        };

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
                document.getElementById('userName').textContent = formData.name;

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
});