from flask import Flask, request, jsonify
import redis
import json
import pika
import threading
import uuid
import os
from datetime import datetime

app = Flask(__name__)

redis_client = redis.Redis(
    host=os.getenv('REDIS_HOST', 'localhost'), 
    port=int(os.getenv('REDIS_PORT', 6379)), 
    decode_responses=True
)

events_list = []

def setup_rabbitmq():
    try:
        rabbitmq_host = os.getenv('RABBITMQ_HOST', 'localhost')
        rabbitmq_port = int(os.getenv('RABBITMQ_PORT', 5672))
        rabbitmq_user = os.getenv('RABBITMQ_USER', 'guest')
        rabbitmq_pass = os.getenv('RABBITMQ_PASS', 'guest')
        
        credentials = pika.PlainCredentials(rabbitmq_user, rabbitmq_pass)
        connection = pika.BlockingConnection(
            pika.ConnectionParameters(
                host=rabbitmq_host,
                port=rabbitmq_port,
                credentials=credentials
            )
        )
        channel = connection.channel()
        
        channel.queue_declare(queue='logistics_queue', durable=True)
        
        return connection, channel
    except Exception as e:
        print(f"Erro ao conectar com RabbitMQ: {e}")
        return None, None

def rabbitmq_consumer():
    try:
        connection, channel = setup_rabbitmq()
        if not connection:
            return
            
        def callback(ch, method, properties, body):
            try:
                message = json.loads(body.decode('utf-8'))
                print(f"Mensagem recebida do RabbitMQ: {message}")
                
                event = {
                    'eventId': str(uuid.uuid4()),
                    'type': 'logistics',
                    'source': 'PHP_Logistics_API',
                    'data': message,
                    'timestamp': datetime.now().isoformat(),
                    'processed': True
                }
                
                events_list.append(event)
                
                try:
                    redis_client.delete('events_cache')
                    redis_client.setex('events_cache', 60, json.dumps(events_list))
                except:
                    pass
                
                print(f"Evento de logística processado: {event['eventId']}")
                ch.basic_ack(delivery_tag=method.delivery_tag)
                
            except Exception as e:
                print(f"Erro ao processar mensagem: {e}")
                ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)
        
        channel.basic_consume(queue='logistics_queue', on_message_callback=callback)
        print("Aguardando mensagens do RabbitMQ...")
        channel.start_consuming()
        
    except Exception as e:
        print(f"Erro no consumer RabbitMQ: {e}")

@app.route('/event', methods=['POST'])
def receive_event():
    try:
        data = request.get_json()
        
        if not data or 'message' not in data:
            return jsonify({'error': 'Dados inválidos'}), 400
        
        event = {
            'eventId': str(uuid.uuid4()),
            'type': 'alert',
            'source': data.get('source', 'Unknown'),
            'message': data['message'],
            'severity': data.get('severity', 'medium'),
            'sensorId': data.get('sensorId', 'N/A'),
            'timestamp': data.get('timestamp', datetime.now().isoformat()),
            'receivedAt': datetime.now().isoformat()
        }
        
        events_list.append(event)
        
        try:
            redis_client.delete('events_cache')
            redis_client.setex('events_cache', 60, json.dumps(events_list))
        except Exception as e:
            print(f"Erro ao atualizar cache Redis: {e}")
        
        print(f"Evento recebido: {event['eventId']}")
        
        return jsonify({
            'message': 'Evento registrado com sucesso',
            'eventId': event['eventId'],
            'timestamp': event['receivedAt']
        }), 201
        
    except Exception as e:
        print(f"Erro ao processar evento: {e}")
        return jsonify({'error': 'Erro interno do servidor'}), 500

@app.route('/events', methods=['GET'])
def get_events():
    try:
        cached_events = redis_client.get('events_cache')
        
        if cached_events:
            print("Eventos retornados do cache Redis")
            events = json.loads(cached_events)
            return jsonify({
                'source': 'cache',
                'total': len(events),
                'events': events
            })
        
        if events_list:
            redis_client.setex('events_cache', 60, json.dumps(events_list))
        
        print("Eventos retornados da memória")
        return jsonify({
            'source': 'memory',
            'total': len(events_list),
            'events': events_list
        })
        
    except Exception as e:
        print(f"Erro ao buscar eventos: {e}")
        return jsonify({'error': 'Erro interno do servidor'}), 500

if __name__ == '__main__':
    consumer_thread = threading.Thread(target=rabbitmq_consumer, daemon=True)
    consumer_thread.start()
    
    print("API Python (Eventos Críticos) iniciando na porta 5000...")

    app.run(host='0.0.0.0', port=5000, debug=True, threaded=True)