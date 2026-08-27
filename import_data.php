<?php
// import_data.php
// Запустите этот скрипт один раз для импорта данных

$dataFile = __DIR__ . '/gt7_data.json';

// Данные из ваших Google Sheets
$admins = [
    [
        'id' => 'Aleksey',
        'name' => 'Алексей',
        'active' => true,
        'display_mode' => 'all',
        'car_name' => 'Nissan Fairlady Z Version S (Z33) \'07',
        'track_name' => 'США Blue Moon Bay Speedway - внутренняя A',
        'laps' => 10,
        'weather_name' => 'S12 (Солнечно, муссонные облака)',
        'time_name' => 'Закат',
        'time_multiplier' => 'X18',
        'tires_value' => 'X9',
        'fuel_value' => 'X7',
        'category_name' => 'Gr.3',
        'car_locked' => false,
        'track_locked' => false,
        'weather_locked' => false,
        'time_locked' => false,
        'tires_locked' => false,
        'fuel_locked' => false,
        'category_locked' => false,
        'similar_car_1' => 'Hyundai Genesis Coupe 3.8 \'13',
        'similar_car_2' => 'Nissan Fairlady Z (Z34) \'08',
        'similar_car_3' => 'Audi TTS Coupé \'14',
        'similar_car_4' => 'Subaru Impreza Sedan WRX STi \'04',
        'similar_car_5' => 'Honda Civic Type R (FL5) \'22'
    ],
    [
        'id' => 'Egor',
        'name' => 'Егор',
        'active' => true,
        'display_mode' => 'group_only',
        'car_name' => 'Super Formula SF23 Super Formula / Toyota \'23',
        'track_name' => 'США Trial Mountain Circuit',
        'laps' => 6,
        'weather_name' => 'S10 (Солнечно, дымка от тумана)',
        'time_name' => 'Ночь',
        'time_multiplier' => 'X9',
        'tires_value' => 'X9',
        'fuel_value' => 'X8',
        'category_name' => 'Gr.3',
        'car_locked' => false,
        'track_locked' => false,
        'weather_locked' => false,
        'time_locked' => false,
        'tires_locked' => false,
        'fuel_locked' => false,
        'category_locked' => false,
        'similar_car_1' => 'Porsche 917K \'70',
        'similar_car_2' => 'Hyundai N 2025 VGT (Gr.1)',
        'similar_car_3' => 'Super Formula SF23 Super Formula / Honda \'23',
        'similar_car_4' => 'Peugeot L750R Hybrid VGT 2017',
        'similar_car_5' => 'Super Formula SF19 Super Formula / Toyota \'19'
    ],
    [
        'id' => 'admin1',
        'name' => 'Администратор 1',
        'active' => true,
        'display_mode' => 'all',
        'car_name' => 'Renault Mégane R.S. Trophy \'11',
        'track_name' => 'Италия Sardegna - Road Track - B',
        'laps' => 13,
        'weather_name' => 'S02 (Ясно, малооблачно)',
        'time_name' => 'Вечер',
        'time_multiplier' => 'X17',
        'tires_value' => 'X8',
        'fuel_value' => 'X19',
        'category_name' => 'Gr.4',
        'car_locked' => false,
        'track_locked' => false,
        'weather_locked' => false,
        'time_locked' => false,
        'tires_locked' => false,
        'fuel_locked' => false,
        'category_locked' => false,
        'similar_car_1' => 'Audi TTS Coupé \'14',
        'similar_car_2' => 'Renault Mégane R.S. Trophy Safety Car',
        'similar_car_3' => 'Volkswagen Scirocco R \'10',
        'similar_car_4' => 'Ford Focus ST \'15',
        'similar_car_5' => 'Mitsubishi Lancer Evolution VIII MR GSR \'04'
    ],
    [
        'id' => 'admin2',
        'name' => 'Администратор 2',
        'active' => true,
        'display_mode' => 'all',
        'car_name' => 'Mercedes-Benz 190 E 2.5-16 Evolution II \'91',
        'track_name' => 'Япония Fuji International Speedway',
        'laps' => 9,
        'weather_name' => 'R01 (Мелкий дождь, морось)',
        'time_name' => 'Закат',
        'time_multiplier' => 'X9',
        'tires_value' => 'X8',
        'fuel_value' => 'X20',
        'category_name' => 'Gr.3',
        'car_locked' => false,
        'track_locked' => false,
        'weather_locked' => false,
        'time_locked' => false,
        'tires_locked' => false,
        'fuel_locked' => false,
        'category_locked' => false,
        'similar_car_1' => 'Nissan 180SX Type X \'96',
        'similar_car_2' => 'BMW M3 Sport Evolution \'89',
        'similar_car_3' => 'Renault Clio V6 24V \'00',
        'similar_car_4' => 'Nissan Skyline GTS-R (R31) \'87',
        'similar_car_5' => 'Toyota 86 GT"Limited" \'16'
    ]
];

// Данные гонок из Google Sheets
$races = [
    [
        'id' => '925eeb87-2e42-4387-beac-5e12e14deeef',
        'adminId' => 'Aleksey',
        'datetime' => '2026-06-10 19:30:00',
        'title' => '255.0',
        'display_mode' => 'group_only',
        'car_name' => '',
        'track_name' => 'Япония Tokyo Expressway - Юг (против часовой)',
        'category_name' => '⚡ Super Formula (Автомобили Super Formula SF19/SF23)',
        'weather_name' => '☁️ S17 (Солнечно, высокая облачность)',
        'time_name' => '🌅 Заря',
        'time_multiplier' => 'X14',
        'tires_value' => 'X15',
        'fuel_value' => 'X16',
        'laps' => 8,
        'active' => true,
        'synced' => true,
        'created_at' => '2026-06-09T11:26:15.217Z',
        'car_locked' => false,
        'track_locked' => false,
        'weather_locked' => false,
        'time_locked' => false,
        'tires_locked' => false,
        'fuel_locked' => false,
        'category_locked' => false
    ]
];

// Формируем итоговые данные
$data = [
    'admins' => $admins,
    'races' => $races
];

// Сохраняем в файл
if (file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "✅ Данные успешно импортированы!\n";
    echo "Администраторов: " . count($admins) . "\n";
    echo "Гонок: " . count($races) . "\n";
    echo "\n📋 Администраторы:\n";
    foreach ($admins as $admin) {
        echo "  - " . $admin['id'] . " (" . $admin['name'] . ")\n";
    }
} else {
    echo "❌ Ошибка сохранения данных. Проверьте права на запись.\n";
}
