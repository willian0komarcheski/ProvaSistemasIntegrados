const express = require('express');
const redis = require('redis');
const axios = require('axios');

const app = express();
const PORT = 3000;

const client = redis.createClient({
    url: `redis://${process.env.REDIS_HOST}:${process.env.REDIS_PORT}`
});


client.on('error', (err) => {
    console.log('Redis Client Error', err);
});

client.connect();

app.use(express.json());

function generateSensorData() {
    return {
        timestamp: new Date().toISOString(),
        temperature: Math.round((Math.random() * 50 + 20) * 100) / 100,
        pressure: Math.round((Math.random() * 100 + 50) * 100) / 100,
        sensorId: `SENSOR_${Math.floor(Math.random() * 10) + 1}`,
        location: `Poço ${Math.floor(Math.random() * 5) + 1}`
    };
}

app.get('/sensor-data', async (req, res) => {
    try {
        const cacheKey = 'sensor_data';
        
        const cachedData = await client.get(cacheKey);
        
        if (cachedData) {
            console.log('Dados retornados do cache Redis');
            return res.json({
                source: 'cache',
                data: JSON.parse(cachedData)
            });
        }
        
        const sensorData = generateSensorData();
        
        await client.setEx(cacheKey, 30, JSON.stringify(sensorData));
        
        console.log('Novos dados gerados e armazenados no cache');
        res.json({
            source: 'generated',
            data: sensorData
        });
        
    } catch (error) {
        console.error('Erro ao acessar Redis:', error);
        res.status(500).json({ error: 'Erro interno do servidor' });
    }
});

app.post('/alert', async (req, res) => {
    try {
        const { message, severity, sensorId } = req.body;
        
        if (!message || !severity) {
            return res.status(400).json({ error: 'Message e severity são obrigatórios' });
        }
        
        const alertData = {
            message,
            severity,
            sensorId: sensorId || 'UNKNOWN',
            timestamp: new Date().toISOString(),
            source: 'NodeJS_Sensor_API'
        };
        
        const pythonApiUrl = process.env.PYTHON_API_URL || 'http://localhost:5000';
        const response = await axios.post(`${pythonApiUrl}/event`, alertData, {
            timeout: 5000
        });
        
        console.log('Alerta enviado para API Python:', alertData);
        res.json({
            message: 'Alerta enviado com sucesso',
            alertId: response.data.eventId || 'N/A',
            sentAt: alertData.timestamp
        });
        
    } catch (error) {
        console.error('Erro ao enviar alerta:', error.message);
        res.status(500).json({ 
            error: 'Falha ao enviar alerta para API Python',
            details: error.message 
        });
    }
});

app.listen(PORT, () => {
    console.log(`API Node.js (Sensores) rodando na porta ${PORT}`);
});

module.exports = app;