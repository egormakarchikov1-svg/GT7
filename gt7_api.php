<?php
// ============================================
// GT7 Random Race API
// Версия: 2.0 - Полная интеграция с данными
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dataFile = __DIR__ . '/gt7_data.json';
$participantsFile = __DIR__ . '/gt7_participants.json';

// ============================================
// ФУНКЦИИ РАБОТЫ С ДАННЫМИ
// ============================================

function loadData($file) {
    if (!file_exists($file)) {
        $default = ['admins' => [], 'races' => []];
        file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return $data ?: ['admins' => [], 'races' => []];
}

function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function loadParticipants($raceId = null) {
    global $participantsFile;
    if (!file_exists($participantsFile)) {
        file_put_contents($participantsFile, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    $data = json_decode(file_get_contents($participantsFile), true) ?: [];
    if ($raceId) {
        return $data[$raceId] ?? [];
    }
    return $data;
}

function saveParticipants($data) {
    global $participantsFile;
    file_put_contents($participantsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generateId() {
    return uniqid() . '_' . bin2hex(random_bytes(8));
}

function getAdminData($adminId) {
    $data = loadData($dataFile);
    // Находим данные администратора в листе
    $adminSheet = null;
    foreach ($data['admins'] as $admin) {
        if ($admin['id'] === $adminId) {
            $adminSheet = $admin;
            break;
        }
    }
    return $adminSheet;
}

function updateAdminData($adminId, $newData) {
    $data = loadData($dataFile);
    foreach ($data['admins'] as &$admin) {
        if ($admin['id'] === $adminId) {
            foreach ($newData as $key => $value) {
                if ($key !== 'id' && $key !== 'name') {
                    $admin[$key] = $value;
                }
            }
            $admin['updated_at'] = date('c');
            break;
        }
    }
    saveData($dataFile, $data);
    return true;
}

// ============================================
// ОБРАБОТКА ЗАПРОСОВ
// ============================================

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$inputData = [];
if ($method === 'POST' || $method === 'PUT') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
}

$params = array_merge($_GET, $inputData);

switch ($action) {
    
    // ===== ПОЛУЧЕНИЕ ДАННЫХ АДМИНИСТРАТОРА =====
    case 'getAdminData':
        $adminId = $params['adminId'] ?? '';
        if (empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'adminId обязателен']);
            break;
        }
        
        $adminData = getAdminData($adminId);
        if ($adminData) {
            echo json_encode(['success' => true, 'data' => $adminData]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Администратор не найден']);
        }
        break;
    
    // ===== ОБНОВЛЕНИЕ ДАННЫХ АДМИНИСТРАТОРА =====
    case 'updateAdminData':
        $adminId = $params['adminId'] ?? '';
        $data = $params['data'] ?? [];
        
        if (empty($adminId) || empty($data)) {
            echo json_encode(['success' => false, 'error' => 'adminId и data обязательны']);
            break;
        }
        
        if (updateAdminData($adminId, $data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка обновления']);
        }
        break;
    
    // ===== ПОЛУЧЕНИЕ ВСЕХ АДМИНИСТРАТОРОВ =====
    case 'getAdmins':
        $data = loadData($dataFile);
        $admins = array_map(function($admin) {
            return [
                'id' => $admin['id'],
                'name' => $admin['name']
            ];
        }, $data['admins'] ?? []);
        
        echo json_encode([
            'success' => true,
            'admins' => $admins
        ]);
        break;
    
    // ===== ДОБАВЛЕНИЕ АДМИНИСТРАТОРА =====
    case 'addAdmin':
        $id = $params['id'] ?? '';
        $name = $params['name'] ?? '';
        $password = $params['password'] ?? '';
        
        if (empty($id) || empty($name) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Все поля обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        
        // Проверяем, не существует ли уже
        foreach ($data['admins'] as $admin) {
            if ($admin['id'] === $id) {
                echo json_encode(['success' => false, 'error' => 'Администратор уже существует']);
                break 2;
            }
        }
        
        $newAdmin = [
            'id' => $id,
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'active' => true,
            'display_mode' => 'all',
            'car_name' => '',
            'track_name' => '',
            'laps' => 5,
            'weather_name' => 'S01 (Ясно, безоблачно)',
            'time_name' => 'День',
            'time_multiplier' => 'X1',
            'tires_value' => 'X1',
            'fuel_value' => 'X1',
            'category_name' => 'Gr.3',
            'car_locked' => false,
            'track_locked' => false,
            'weather_locked' => false,
            'time_locked' => false,
            'tires_locked' => false,
            'fuel_locked' => false,
            'category_locked' => false,
            'similar_car_1' => '',
            'similar_car_2' => '',
            'similar_car_3' => '',
            'similar_car_4' => '',
            'similar_car_5' => '',
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];
        
        $data['admins'][] = $newAdmin;
        saveData($dataFile, $data);
        
        echo json_encode([
            'success' => true,
            'admin' => $newAdmin
        ]);
        break;
    
    // ===== ПРОВЕРКА АДМИНИСТРАТОРА =====
    case 'checkAdmin':
        $adminId = $params['adminId'] ?? '';
        $password = $params['password'] ?? '';
        
        $data = loadData($dataFile);
        $found = null;
        
        foreach ($data['admins'] as $admin) {
            if ($admin['id'] === $adminId) {
                if (password_verify($password, $admin['password'])) {
                    $found = $admin;
                    break;
                }
            }
        }
        
        if ($found) {
            echo json_encode([
                'success' => true,
                'admin_id' => $found['id'],
                'name' => $found['name']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Неверный ID или пароль'
            ]);
        }
        break;
    
    // ===== ПОЛУЧЕНИЕ ГОНОК ПОЛЬЗОВАТЕЛЯ =====
    case 'getUserRaces':
        $adminId = $params['adminId'] ?? '';
        $userId = $params['userId'] ?? 'default';
        
        if (empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'adminId обязателен']);
            break;
        }
        
        $data = loadData($dataFile);
        $now = time();
        $races = [];
        
        foreach ($data['races'] as $race) {
            if ($race['adminId'] === $adminId && $race['active'] === true) {
                $raceTime = strtotime($race['datetime']);
                if ($raceTime > $now) {
                    $races[] = $race;
                }
            }
        }
        
        usort($races, function($a, $b) {
            return strtotime($a['datetime']) - strtotime($b['datetime']);
        });
        
        echo json_encode([
            'success' => true,
            'races' => $races
        ]);
        break;
    
    // ===== ПОЛУЧЕНИЕ ТЕКУЩЕЙ КОМБИНАЦИИ =====
    case 'getCurrentCombo':
        $adminId = $params['adminId'] ?? '';
        
        if (empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'adminId обязателен']);
            break;
        }
        
        $adminData = getAdminData($adminId);
        if ($adminData && $adminData['active'] === true) {
            // Возвращаем данные администратора
            echo json_encode($adminData);
        } else {
            echo json_encode(['success' => false, 'error' => 'Нет активных данных']);
        }
        break;
    
    // ===== ПОЛУЧЕНИЕ ГОНКИ ПО ID =====
    case 'getRaceById':
        $raceId = $params['raceId'] ?? '';
        
        if (empty($raceId)) {
            echo json_encode(['success' => false, 'error' => 'raceId обязателен']);
            break;
        }
        
        $data = loadData($dataFile);
        $race = null;
        
        foreach ($data['races'] as $r) {
            if ($r['id'] === $raceId) {
                $race = $r;
                break;
            }
        }
        
        if ($race) {
            echo json_encode(['success' => true, 'race' => $race]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Гонка не найдена']);
        }
        break;
    
    // ===== НАЗНАЧЕНИЕ ГОНКИ =====
    case 'scheduleRace':
        $adminId = $params['adminId'] ?? '';
        $datetime = $params['datetime'] ?? '';
        $title = $params['title'] ?? '🏁 Гоночный заезд';
        $combo = isset($params['combo']) ? json_decode($params['combo'], true) : [];
        
        if (empty($adminId) || empty($datetime)) {
            echo json_encode(['success' => false, 'error' => 'adminId и datetime обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        
        $newRace = [
            'id' => generateId(),
            'adminId' => $adminId,
            'datetime' => $datetime,
            'title' => $title,
            'active' => true,
            'synced' => false,
            'created_at' => date('c'),
            'display_mode' => $combo['display_mode'] ?? 'all',
            'car_name' => $combo['car_name'] ?? '',
            'track_name' => $combo['track_name'] ?? '',
            'category_name' => $combo['category_name'] ?? '',
            'weather_name' => $combo['weather_name'] ?? '',
            'time_name' => $combo['time_name'] ?? '',
            'time_multiplier' => $combo['time_multiplier'] ?? 'X1',
            'tires_value' => $combo['tires_value'] ?? 'X1',
            'fuel_value' => $combo['fuel_value'] ?? 'X1',
            'laps' => $combo['laps'] ?? 5,
            'car_locked' => $combo['car_locked'] ?? false,
            'track_locked' => $combo['track_locked'] ?? false,
            'weather_locked' => $combo['weather_locked'] ?? false,
            'time_locked' => $combo['time_locked'] ?? false,
            'tires_locked' => $combo['tires_locked'] ?? false,
            'fuel_locked' => $combo['fuel_locked'] ?? false,
            'category_locked' => $combo['category_locked'] ?? false
        ];
        
        for ($i = 1; $i <= 5; $i++) {
            $key = "similar_car_{$i}";
            if (isset($combo[$key])) {
                $newRace[$key] = $combo[$key];
            }
        }
        
        $data['races'][] = $newRace;
        saveData($dataFile, $data);
        
        echo json_encode([
            'success' => true,
            'race' => $newRace
        ]);
        break;
    
    // ===== УДАЛЕНИЕ ГОНКИ =====
    case 'deleteRace':
        $raceId = $params['raceId'] ?? '';
        $adminId = $params['adminId'] ?? '';
        
        if (empty($raceId) || empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'raceId и adminId обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        $data['races'] = array_filter($data['races'], function($race) use ($raceId, $adminId) {
            return !($race['id'] === $raceId && $race['adminId'] === $adminId);
        });
        $data['races'] = array_values($data['races']);
        saveData($dataFile, $data);
        
        echo json_encode(['success' => true]);
        break;
    
    // ===== УЧАСТНИКИ =====
    case 'getParticipants':
        $raceId = $params['raceId'] ?? '';
        $participants = loadParticipants($raceId);
        echo json_encode(['success' => true, 'participants' => $participants]);
        break;
    
    case 'registerForRace':
        $raceId = $params['raceId'] ?? '';
        $name = $params['name'] ?? '';
        $deviceId = $params['deviceId'] ?? '';
        
        if (empty($raceId) || empty($name) || empty($deviceId)) {
            echo json_encode(['success' => false, 'error' => 'Не все данные заполнены']);
            break;
        }
        
        $participants = loadParticipants();
        
        if (isset($participants[$raceId])) {
            foreach ($participants[$raceId] as $p) {
                if ($p['device_id'] === $deviceId) {
                    echo json_encode(['success' => false, 'error' => 'Вы уже зарегистрированы']);
                    break 2;
                }
            }
        }
        
        $newParticipant = [
            'id' => generateId(),
            'name' => $name,
            'device_id' => $deviceId,
            'registered_at' => date('c')
        ];
        
        if (!isset($participants[$raceId])) {
            $participants[$raceId] = [];
        }
        $participants[$raceId][] = $newParticipant;
        saveParticipants($participants);
        
        echo json_encode(['success' => true]);
        break;
    
    case 'cancelRegistration':
        $raceId = $params['raceId'] ?? '';
        $deviceId = $params['deviceId'] ?? '';
        
        if (empty($raceId) || empty($deviceId)) {
            echo json_encode(['success' => false, 'error' => 'raceId и deviceId обязательны']);
            break;
        }
        
        $participants = loadParticipants();
        
        if (isset($participants[$raceId])) {
            $participants[$raceId] = array_filter($participants[$raceId], function($p) use ($deviceId) {
                return $p['device_id'] !== $deviceId;
            });
            $participants[$raceId] = array_values($participants[$raceId]);
            saveParticipants($participants);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Участник не найден']);
        }
        break;
    
    case 'getUserRegistrations':
        $adminId = $params['adminId'] ?? '';
        $deviceId = $params['deviceId'] ?? '';
        
        if (empty($adminId) || empty($deviceId)) {
            echo json_encode(['success' => false, 'error' => 'adminId и deviceId обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        $participants = loadParticipants();
        $registeredRaces = [];
        
        $adminRaces = [];
        foreach ($data['races'] as $race) {
            if ($race['adminId'] === $adminId) {
                $adminRaces[$race['id']] = $race;
            }
        }
        
        foreach ($participants as $raceId => $list) {
            if (isset($adminRaces[$raceId])) {
                foreach ($list as $p) {
                    if ($p['device_id'] === $deviceId) {
                        $registeredRaces[] = $raceId;
                        break;
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'races' => $registeredRaces]);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Неизвестное действие: ' . $action
        ]);
        break;
}
