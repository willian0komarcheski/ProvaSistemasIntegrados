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
```

```bash
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
```

```bash
# Get all events
curl http://localhost:5000/events
```

### Logistics API (PHP - Port 8000)

```bash
# Get equipment list
curl http://localhost:8000/equipments
```

```bash
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
ao ser consumida, procura no cache se existe dado salvo nele com a chave "sensor_data", se existir retorna esse dado, se não existir gera um dado de sensor aleatorio de acordo com a função "generateSensorData()", depois salva no cache com a mesma chave de procura e retorna um json com o resultado gerado.

#### `POST /alert`

**Descrição:**
ao ser consumida, cria uma variavel que recebe o body da chamada, recebendo a message, severity e sensorId, depois cria uma variavel com todos esses dados e adiciona o tempo atual e a fonte (NodeJs_sensor_api), depois envia esses dados para a api Python localizada na porta 5000, endpoint /event e metodo POST, isso usando o axios (biblioteca para para chamadas http), dessa chamada recebe um eventId que logo em seguida é retornado junto com a mensagem de alerta enviado com sucesso em um json.

## Critical Events API (Python + Flask)

API desenvolvida em Python com Flask, que gerencia eventos de alerta e logística, utilizando Redis para cache e RabbitMQ para consumo de mensagens assíncronas.

### Endpoints da API

#### `POST /event`

**Descrição:**
ao ser consumida, pega o body enviado e transforma em variavel, depois cria um dicionario com as informações do body para estruturar a informação, depois salva na list_events e atualiza o redis com essa lista list_events, apos isso retorna um json com uma mensagem de sucesso, eventId, tempo atual e codigo de sucesso (201).

#### `GET /events`

**Descrição:**
ao ser consumida, verifica se existe dados no cache do Redis com a chave 'events_cache'. Se existir retorna os dados do cache em json, se não retorna os dados da lista (events_list) em json e atualiza o cache do Redis.

#### `fila Rabbitmq (não é um endpoint mas achei importante colocar)`

**Descrição:**
a fila é configurada na função setup_rabbitmq() com as variaveis de ambiente, e a configuração do que fazer quando a fila receber um aviso foi declarada no rabbitmq_consumer(), ele da um setup de configurações de comportamento da fila de consumo,  e define o callback(ch, method, properties, body) como sendo oque ela ira fazer quando receber a notificação de evento, nesse calback ela carrega os dados do body recebido, estrutura os dados em um dicionario, adiciona na lista (events_list) e salva no redis a lista.

## Módulo de Logística (PHP)

API escrita em PHP que simula um sistema de gerenciamento logístico para equipamentos industriais. Utiliza RabbitMQ para publicação de mensagens e responde a requisições REST simples.

### Endpoints da API

#### `fila Rabbitmq (não é um endpoint mas achei importante colocar)`

**Descrição:**
a fila é cuidada inteiramente pela função publishToRabbitMQ($message), no começo ela adquire as variaveis de ambiente para configuração de conexão de fila, depois cria a ligação com a fila usando essas variaveis, cria uma variavel para representar essa conexão, declara uma fila com o nome "logistic_queue", cria uma mensagem com e codifica em json a $message (variavel da função) para enviar nessa mensagem, tambem declara o modo de entrega nessa mensagem, depois publica essa mensagem na fila e fecha a conexão.

#### `GET /equipments`

**Descrição:**
ao ser consumida, retorna uma lista de equipamentos industriais, usa a getEquipmentsList() para pegar os equipamento armazenados em uma lista estatica, os dados são retornados em json junto com  nome do serviço, numero de equipamentos e tempo atual.

#### `POST /dispatch`

**Descrição:**
ao ser consumida, decodifica o json e transforma em variavel, verifica os dados, estrutura os dados em um dicionario e chama o metodo que cuida da fila rabbitmq para enviar os dados do dicionario para a fila, verifica se foi enviado pra fila e retorna um json com os dados do dicionario, uma mensagem de sucesso, status e tempo atual.