const WebSocket = require('ws');
const { randomUUID } = require('crypto');

const VOLCANO_URL = 'wss://openspeech.bytedance.com/api/v3/realtime/dialogue';
const PORT = 8081;

const server = new WebSocket.Server({ port: PORT });

server.on('connection', (clientWs, req) => {
  console.log('--- 新客户端接入 ---');
  const urlParams = new URLSearchParams(req.url.split('?')[1] || '');
  const appId = urlParams.get('appid');
  const accessKey = urlParams.get('accesskey');

  const volcanoWs = new WebSocket(VOLCANO_URL, {
    headers: {
      'X-Api-App-ID': appId,
      'X-Api-Access-Key': accessKey,
      'X-Api-Resource-Id': 'seed-icl-1.0',
      'X-Api-App-Key': 'PlgvMymc7f3tQnJ6',
      'X-Api-Connect-Id': randomUUID()
    }
  });

  let messageQueue = [];

  volcanoWs.on('open', () => {
    console.log('✅ 代理 -> 火山：连接成功');
    while (messageQueue.length > 0) {
      volcanoWs.send(messageQueue.shift());
    }
  });

  volcanoWs.on('message', (data) => {
    // 关键：如果是文本消息，打印出来看看火山说了什么
    try {
      const str = data.toString();
      if (str.startsWith('{')) {
        console.log('📩 火山返回消息:', str);
      }
    } catch (e) {}
    
    if (clientWs.readyState === WebSocket.OPEN) clientWs.send(data);
  });

    // 在 proxy.js 中定义打包函数
    function packV3Message(payload, messageType) {
        const header = Buffer.alloc(28);
        const payloadBuffer = Buffer.isBuffer(payload) ? payload : Buffer.from(payload);
    
        header.writeUInt32BE(0x11223344, 0); // Magic
        header.writeUInt8(0x01, 4);          // Version
        header.writeUInt8(0x01, 5);          // Header Length
        header.writeUInt8(messageType, 6);   // Message Type: 1-JSON, 2-Audio
        header.writeUInt8(0x01, 7);          // Message Type Specific Flags
        header.writeUInt8(0x01, 8);          // Serialization: 1-JSON
        header.writeUInt8(0x00, 9);          // No compression
        header.writeUInt32BE(payloadBuffer.length, 20); // Payload Length
        header.writeUInt32BE(0x00, 24);      // Sequence Number
    
        return Buffer.concat([header, payloadBuffer]);
    }
    
    // 修改 clientWs.on('message')
    clientWs.on('message', (data) => {
        if (volcanoWs.readyState === WebSocket.OPEN) {
            let packedData;
            try {
                // 判断是 JSON 字符串还是二进制音频
                const str = data.toString();
                if (str.startsWith('{')) {
                    packedData = packV3Message(str, 0x1); // JSON 消息
                } else {
                    packedData = packV3Message(data, 0x2); // 音频消息
                }
                volcanoWs.send(packedData);
            } catch (e) {
                packedData = packV3Message(data, 0x2);
                volcanoWs.send(packedData);
            }
        }
    });
 

  volcanoWs.on('close', (code, reason) => {
    console.log(`❌ 火山主动断开：代码 ${code}, 原因: ${reason}`);
    clientWs.close();
  });

  volcanoWs.on('error', (err) => console.error('🔥 火山连接错误:', err));
  clientWs.on('close', () => volcanoWs.close());
});

console.log(`🚀 代理运行在端口 ${PORT}`);