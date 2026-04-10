const WebSocket = require('ws');
const { randomUUID } = require('crypto');

const VOLCANO_URL = 'wss://openspeech.bytedance.com/api/v3/realtime/dialogue';
const PORT = 8081;

const server = new WebSocket.Server({ port: PORT });

server.on('connection', (clientWs, req) => {
  console.log('客户端连接:', req.socket.remoteAddress);

  const urlParams = new URLSearchParams(req.url.split('?')[1] || '');
  const appId = urlParams.get('appid');
  const accessKey = urlParams.get('accesskey');

  if (!appId || !accessKey) {
    clientWs.close(4000, '缺少凭证');
    return;
  }

  const volcanoWs = new WebSocket(VOLCANO_URL, {
    headers: {
      'X-Api-App-ID': appId,
      'X-Api-Access-Key': accessKey,
      'X-Api-Resource-Id': 'volc.speech.dialog',
      'X-Api-App-Key': 'PlgvMymc7f3tQnJ6',
      'X-Api-Connect-Id': randomUUID()
    }
  });

  volcanoWs.on('open', () => console.log('✅ 已成功连接火山引擎'));
  volcanoWs.on('message', (data) => clientWs.send(data));
  volcanoWs.on('close', () => clientWs.close());
  volcanoWs.on('error', (err) => console.error('火山错误', err));

  clientWs.on('message', (data) => {
    if (volcanoWs.readyState === WebSocket.OPEN) volcanoWs.send(data);
  });

  clientWs.on('close', () => volcanoWs.close());
  clientWs.on('error', () => volcanoWs.close());
});

console.log(`✅ 代理启动成功：ws://8.162.9.154:${PORT}`);