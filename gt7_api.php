<?php
// ============================================
// GT7 Random Race API
// Версия: 1.0
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Файл для хранения данных
$dataFile = __DIR__ . '/gt7_data.json';
$participantsFile = __DIR__ . '/gt7_participants.json';

// ============================================
// ФУНКЦИИ РАБОТЫ С ДАННЫМИ
// ============================================

function loadData($file) {
    if (!file_exists($file)) {
        // Создаём пустой файл с базовой структурой
        $default = ['admins' => [], 'races' => []];
        file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: ['admins' => [], 'races' => []];
}

function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function loadParticipants($raceId = null) {
    global $participantsFile;
    $data = loadData($participantsFile);
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

// ============================================
// ОБРАБОТКА ЗАПРОСОВ
// ============================================

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Получаем данные из тела запроса для POST/PUT
$inputData = [];
if ($method === 'POST' || $method === 'PUT') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
}

// Объединяем с GET параметрами
$params = array_merge($_GET, $inputData);

// ============================================
// ДЕЙСТВИЯ
// ============================================

switch ($action) {
    
    // ===== АДМИНИСТРАТОРЫ =====
    case 'getAdmins':
        $data = loadData($dataFile);
        echo json_encode([
            'success' => true,
            'admins' => $data['admins'] ?? []
        ]);
        break;
    
    case 'addAdmin':
        $name = $params['name'] ?? '';
        $password = $params['password'] ?? '';
        
        if (empty($name) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Имя и пароль обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        $newAdmin = [
            'id' => generateId(),
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('c')
        ];
        $data['admins'][] = $newAdmin;
        saveData($dataFile, $data);
        
        echo json_encode([
            'success' => true,
            'admin' => $newAdmin
        ]);
        break;
    
    case 'checkAdmin':
        $adminId = $params['adminId'] ?? '';
        $password = $params['password'] ?? '';
        
        $data = loadData($dataFile);
        $found = null;
        
        foreach ($data['admins'] as $admin) {
            if ($admin['id'] === $adminId || $admin['name'] === $adminId) {
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
    
    // ===== ГОНКИ =====
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
        
        // Сортируем по дате
        usort($races, function($a, $b) {
            return strtotime($a['datetime']) - strtotime($b['datetime']);
        });
        
        echo json_encode([
            'success' => true,
            'races' => $races
        ]);
        break;
    
    case 'getCurrentCombo':
        $adminId = $params['adminId'] ?? '';
        
        if (empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'adminId обязателен']);
            break;
        }
        
        $data = loadData($dataFile);
        $now = time();
        $currentRace = null;
        $minDiff = PHP_INT_MAX;
        
        foreach ($data['races'] as $race) {
            if ($race['adminId'] === $adminId && $race['active'] === true) {
                $raceTime = strtotime($race['datetime']);
                $diff = $raceTime - $now;
                if ($diff > 0 && $diff < $minDiff) {
                    $minDiff = $diff;
                    $currentRace = $race;
                }
            }
        }
        
        if ($currentRace) {
            echo json_encode($currentRace);
        } else {
            echo json_encode(['success' => false, 'error' => 'Нет активных гонок']);
        }
        break;
    
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
    
    case 'scheduleRace':
        $adminId = $params['adminId'] ?? '';
        $datetime = $params['datetime'] ?? '';
        $title = $params['title'] ?? '🏁 Гоночный заезд';
        
        // Получаем комбо данные
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
        
        // Добавляем похожие авто
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
    
    case 'updateRace':
        $raceId = $params['raceId'] ?? '';
        $adminId = $params['adminId'] ?? '';
        
        if (empty($raceId) || empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'raceId и adminId обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        $found = false;
        
        foreach ($data['races'] as &$race) {
            if ($race['id'] === $raceId && $race['adminId'] === $adminId) {
                // Обновляем поля
                if (isset($params['datetime'])) $race['datetime'] = $params['datetime'];
                if (isset($params['title'])) $race['title'] = $params['title'];
                if (isset($params['active'])) $race['active'] = $params['active'] === 'true';
                if (isset($params['car_name'])) $race['car_name'] = $params['car_name'];
                if (isset($params['track_name'])) $race['track_name'] = $params['track_name'];
                if (isset($params['weather_name'])) $race['weather_name'] = $params['weather_name'];
                if (isset($params['time_name'])) $race['time_name'] = $params['time_name'];
                if (isset($params['time_multiplier'])) $race['time_multiplier'] = $params['time_multiplier'];
                if (isset($params['tires_value'])) $race['tires_value'] = $params['tires_value'];
                if (isset($params['fuel_value'])) $race['fuel_value'] = $params['fuel_value'];
                if (isset($params['display_mode'])) $race['display_mode'] = $params['display_mode'];
                if (isset($params['category_name'])) $race['category_name'] = $params['category_name'];
                if (isset($params['laps'])) $race['laps'] = intval($params['laps']);
                
                $race['updated_at'] = date('c');
                $found = true;
                break;
            }
        }
        
        if ($found) {
            saveData($dataFile, $data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Гонка не найдена']);
        }
        break;
    
    case 'deleteRace':
        $raceId = $params['raceId'] ?? '';
        $adminId = $params['adminId'] ?? '';
        
        if (empty($raceId) || empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'raceId и adminId обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        $initialCount = count($data['races']);
        $data['races'] = array_filter($data['races'], function($race) use ($raceId, $adminId) {
            return !($race['id'] === $raceId && $race['adminId'] === $adminId);
        });
        
        if (count($data['races']) < $initialCount) {
            saveData($dataFile, $data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Гонка не найдена']);
        }
        break;
    
    case 'toggleRaceStatus':
        $raceId = $params['raceId'] ?? '';
        $adminId = $params['adminId'] ?? '';
        $active = isset($params['active']) ? $params['active'] === 'true' : false;
        
        if (empty($raceId) || empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'raceId и adminId обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        $found = false;
        
        foreach ($data['races'] as &$race) {
            if ($race['id'] === $raceId && $race['adminId'] === $adminId) {
                $race['active'] = $active;
                $found = true;
                break;
            }
        }
        
        if ($found) {
            saveData($dataFile, $data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Гонка не найдена']);
        }
        break;
    
    case 'syncRaceToUsers':
        $raceId = $params['raceId'] ?? '';
        $adminId = $params['adminId'] ?? '';
        
        if (empty($raceId) || empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'raceId и adminId обязательны']);
            break;
        }
        
        $data = loadData($dataFile);
        $found = false;
        $newSynced = false;
        
        foreach ($data['races'] as &$race) {
            if ($race['id'] === $raceId && $race['adminId'] === $adminId) {
                $race['synced'] = !$race['synced'];
                $newSynced = $race['synced'];
                $found = true;
                break;
            }
        }
        
        if ($found) {
            saveData($dataFile, $data);
            echo json_encode(['success' => true, 'synced' => $newSynced]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Гонка не найдена']);
        }
        break;
    
    case 'deleteOldRaces':
        $adminId = $params['adminId'] ?? '';
        
        if (empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'adminId обязателен']);
            break;
        }
        
        $data = loadData($dataFile);
        $now = time();
        $initialCount = count($data['races']);
        
        $data['races'] = array_filter($data['races'], function($race) use ($adminId, $now) {
            if ($race['adminId'] !== $adminId) return true;
            $raceTime = strtotime($race['datetime']);
            return $raceTime > $now;
        });
        
        $deleted = $initialCount - count($data['races']);
        saveData($dataFile, $data);
        
        echo json_encode(['success' => true, 'deleted' => $deleted]);
        break;
    
    case 'batchActivateRaces':
        $adminId = $params['adminId'] ?? '';
        
        if (empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'adminId обязателен']);
            break;
        }
        
        $data = loadData($dataFile);
        $updated = 0;
        
        foreach ($data['races'] as &$race) {
            if ($race['adminId'] === $adminId && !$race['active']) {
                $race['active'] = true;
                $updated++;
            }
        }
        
        saveData($dataFile, $data);
        echo json_encode(['success' => true, 'updated' => $updated]);
        break;
    
    case 'batchDeactivateRaces':
        $adminId = $params['adminId'] ?? '';
        
        if (empty($adminId)) {
            echo json_encode(['success' => false, 'error' => 'adminId обязателен']);
            break;
        }
        
        $data = loadData($dataFile);
        $updated = 0;
        
        foreach ($data['races'] as &$race) {
            if ($race['adminId'] === $adminId && $race['active']) {
                $race['active'] = false;
                $updated++;
            }
        }
        
        saveData($dataFile, $data);
        echo json_encode(['success' => true, 'updated' => $updated]);
        break;
    
    // ===== УЧАСТНИКИ =====
    case 'getParticipants':
        $raceId = $params['raceId'] ?? '';
        
        if (empty($raceId)) {
            echo json_encode(['success' => false, 'error' => 'raceId обязателен']);
            break;
        }
        
        $participants = loadParticipants($raceId);
        echo json_encode([
            'success' => true,
            'participants' => $participants
        ]);
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
        
        // Проверяем, не зарегистрирован ли уже
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
        
        // Собираем все гонки администратора
        $adminRaces = [];
        foreach ($data['races'] as $race) {
            if ($race['adminId'] === $adminId) {
                $adminRaces[$race['id']] = $race;
            }
        }
        
        // Проверяем регистрации
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
        
        echo json_encode([
            'success' => true,
            'races' => $registeredRaces
        ]);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Неизвестное действие: ' . $action
        ]);
        break;
}
