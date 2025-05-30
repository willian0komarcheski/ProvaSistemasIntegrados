<?php

require_once 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function getEquipmentsList() {
    return [
        [
            'id' => 1,
            'name' => 'Bomba Centrífuga BP-001',
            'type' => 'pump',
            'status' => 'operational',
            'location' => 'Plataforma A',
            'lastMaintenance' => '2024-01-15'
        ],
        [
            'id' => 2,
            'name' => 'Válvula de Segurança VS-201',
            'type' => 'valve',
            'status' => 'maintenance_required',
            'location' => 'Poço 3',
            'lastMaintenance' => '2023-12-10'
        ],
        [
            'id' => 3,
            'name' => 'Compressor de Gás CG-105',
            'type' => 'compressor',
            'status' => 'operational',
            'location' => 'Estação Central',
            'lastMaintenance' => '2024-02-01'
        ],
        [
            'id' => 4,
            'name' => 'Sensor de Pressão SP-089',
            'type' => 'sensor',
            'status' => 'critical',
            'location' => 'Poço 1',
            'lastMaintenance' => '2023-11-20'
        ],
        [
            'id' => 5,
            'name' => 'Tubulação Principal TP-456',
            'type' => 'pipeline',
            'status' => 'operational',
            'location' => 'Linha Principal',
            'lastMaintenance' => '2024-01-30'
        ]
    ];
}

function publishToRabbitMQ($message) {
    try {
        $host = $_ENV['RABBITMQ_HOST'] ?? 'localhost';
        $port = $_ENV['RABBITMQ_PORT'] ?? 5672;
        $user = $_ENV['RABBITMQ_USER'] ?? 'guest';
        $pass = $_ENV['RABBITMQ_PASS'] ?? 'guest';
        
        $connection = new AMQPStreamConnection($host, $port, $user, $pass);
        $channel = $connection->channel();
        
        $channel->queue_declare('logistics_queue', false, true, false, false);
        
        $msg = new AMQPMessage(
            json_encode($message),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        
        $channel->basic_publish($msg, '', 'logistics_queue');
        
        $channel->close();
        $connection->close();
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erro RabbitMQ: " . $e->getMessage());
        return false;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

if ($method === 'GET' && $path === '/equipments') {
    $equipments = getEquipmentsList();
    
    echo json_encode([
        'service' => 'Módulo de Logística',
        'total' => count($equipments),
        'equipments' => $equipments,
        'timestamp' => date('c')
    ]);
    exit;
}

if ($method === 'POST' && $path === '/dispatch') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['equipmentId']) || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'equipmentId e action são obrigatórios']);
        exit;
    }
    
    $logisticsMessage = [
        'dispatchId' => uniqid('DISPATCH_'),
        'equipmentId' => $input['equipmentId'],
        'action' => $input['action'],
        'priority' => $input['priority'] ?? 'normal',
        'description' => $input['description'] ?? 'Operação de logística',
        'requestedBy' => $input['requestedBy'] ?? 'Sistema',
        'timestamp' => date('c'),
        'estimatedTime' => $input['estimatedTime'] ?? '2-4 horas'
    ];
    
    $published = publishToRabbitMQ($logisticsMessage);
    
    if ($published) {
        echo json_encode([
            'message' => 'Solicitação de logística enviada com sucesso',
            'dispatchId' => $logisticsMessage['dispatchId'],
            'status' => 'queued',
            'timestamp' => $logisticsMessage['timestamp']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'Falha ao enviar mensagem para fila',
            'dispatchId' => $logisticsMessage['dispatchId']
        ]);
    }
    exit;
}

if ($method === 'GET' && $path === '/status') {
    echo json_encode([
        'service' => 'Módulo de Logística',
        'status' => 'online',
        'timestamp' => date('c')
    ]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint não encontrado']);