# Executando e Testando a Aplicação (Docker Compose)

Este projeto é composto por três APIs (Node.js, Python e PHP) integradas a serviços de Redis e RabbitMQ.

## Subindo os Serviços

```bash
cd ProvaSistemasIntegrados
docker-compose up --build
```

## Urls para Teste de endpoints 

### Sensor API (Node.js - Port 3000)

```bash
# Get sensor data
curl http://localhost:3000/sensor-data

# Send alert
curl -X POST http://localhost:3000/alert \
-H "Content-Type: application/json" \
-d '{"message": "High temperature alert", "severity": "high", "sensorId": "SENSOR_1"}'
```

### Events API (Python - Port 5000)

```bash
# Send event
curl -X POST http://localhost:5000/event \
-H "Content-Type: application/json" \
-d '{"message": "Critical event detected", "severity": "high", "source": "test"}'

# Get all events
curl http://localhost:5000/events
```

### Logistics API (PHP - Port 8000)

```bash
# Get equipment list
curl http://localhost:8000/equipments

# Send dispatch request
curl -X POST http://localhost:8000/dispatch \
-H "Content-Type: application/json" \
-d '{"equipmentId": 1, "action": "maintenance", "priority": "high"}'
```

# explicação dos endpoints das apis

## Sensor Data API (Node.js)

API desenvolvida em Node.js com uso de Redis para cache e integração com uma API Python externa. Abaixo estão descritos os principais endpoints disponíveis para uso e documentação.

### Endpoints da API

#### `GET /sensor-data`

**Descrição:**
Explique aqui como esse endpoint funciona, o que ele retorna e qual sua utilidade.

#### `POST /alert`

**Descrição:**
Explique aqui como esse endpoint funciona, o que é necessário enviar no corpo da requisição e qual o propósito do alerta.


## Critical Events API (Python + Flask)

API desenvolvida em Python com Flask, que gerencia eventos de alerta e logística, utilizando Redis para cache e RabbitMQ para consumo de mensagens assíncronas.

### Endpoints da API

#### `POST /event`

**Descrição:**
Explique aqui como esse endpoint funciona, quais dados ele espera no corpo da requisição e o que é feito com as informações recebidas.

#### `GET /events`

**Descrição:**
Explique aqui como recuperar a lista de eventos registrados, a diferença entre dados em cache e em memória, e quando esse endpoint pode ser usado.


## Módulo de Logística (PHP)

API escrita em PHP que simula um sistema de gerenciamento logístico para equipamentos industriais. Utiliza RabbitMQ para publicação de mensagens e responde a requisições REST simples.

### Endpoints da API

#### `GET /equipments`

**Descrição:**
Explique aqui como esse endpoint retorna uma lista fixa de equipamentos, com detalhes como status, localização e data da última manutenção.

#### `POST /dispatch`

**Descrição:**
Explique aqui como esse endpoint recebe uma solicitação de despacho logístico para um equipamento e publica essa mensagem na fila `logistics_queue` do RabbitMQ.