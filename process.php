<?php
// Устанавливаем кодировку
header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД
$host = 'localhost';
$dbname = 'u68917';
$user = 'u68917';
$pass = '1300093';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Функция для вывода ошибок
function displayErrors($errors) {
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <title>Ошибки в форме</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <h1>Ошибки при заполнении формы</h1>
        </header>
        <div class="main">
            <ul style="color: red;">';
    
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    
    echo '</ul>
            <p><a href="index.html">Вернуться к форме</a></p>
        </div>
    </body>
    </html>';
    exit;
}

// Проверяем метод отправки
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Валидация данных
$errors = [];

// ФИО
$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) {
    $errors[] = 'ФИО обязательно для заполнения';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $full_name)) {
    $errors[] = 'ФИО должно содержать только буквы и пробелы';
} elseif (strlen($full_name) > 150) {
    $errors[] = 'ФИО должно быть не длиннее 150 символов';
}

// Телефон
$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors[] = 'Телефон обязателен для заполнения';
} elseif (!preg_match('/^\+?[0-9\s\-\(\)]{10,20}$/', $phone)) {
    $errors[] = 'Неверный формат телефона';
}

// Email
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    $errors[] = 'Некорректный email';
}

// Дата рождения
$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors[] = 'Укажите дату рождения';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    $errors[] = 'Неверный формат даты';
}

// Пол
$gender = $_POST['gender'] ?? '';
if (!in_array($gender, ['male', 'female'])) {
    $errors[] = 'Укажите пол';
}

// Языки программирования
$languages = $_POST['languages'] ?? [];
if (empty($languages)) {
    $errors[] = 'Выберите хотя бы один язык';
}

// Биография
$bio = trim($_POST['bio'] ?? '');

// Чекбокс
if (!isset($_POST['contract']) || $_POST['contract'] !== 'on') {
    $errors[] = 'Необходимо принять условия соглашения';
}

// Если есть ошибки - показываем их
if (!empty($errors)) {
    displayErrors($errors);
}

// Сохранение в БД
try {
    $pdo->beginTransaction();
    
    // 1. Сохраняем основную информацию
    $stmt = $pdo->prepare("INSERT INTO applications 
        (full_name, phone, email, birth_date, gender, bio, contract_agreed, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $full_name,
        $phone,
        $email,
        $birth_date,
        $gender,
        $bio,
        isset($_POST['contract']) ? 1 : 0
    ]);

    $app_id = $pdo->lastInsertId();
    
    // 2. Сохраняем языки программирования
    $stmt = $pdo->prepare("INSERT INTO application_languages 
        (application_id, language_id) VALUES (?, ?)");
    
    foreach ($languages as $lang_id) {
        $stmt->execute([$app_id, $lang_id]);
    }
    
    $pdo->commit();
    
    // Сообщение об успехе
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <title>Успех</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <h1>Данные сохранены!</h1>
        </header>
        <div class="main">
            <p>Спасибо! Ваша заявка №'.$app_id.' успешно сохранена.</p>
            <p><a href="index.html">Вернуться к форме</a></p>
        </div>
    </body>
    </html>';

} catch (Exception $e) {
    $pdo->rollBack();
    displayErrors(['Ошибка сохранения: ' . $e->getMessage()]);
}
?>


