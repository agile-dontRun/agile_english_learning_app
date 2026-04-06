// server.js (需在宝塔重启)
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
// 【关键】强制 CORS 中间件 - 对所有 Express 路由生效
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
  if (req.method === 'OPTIONS') {
    return res.sendStatus(200);
  }
  next();
});
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

const rooms = {};
const GEM_TYPES = ['oil', 'diamond', 'ruby', 'emerald', 'amethyst', 'gold', 'silver', 'coin'];

function generateGems() {
    let gems = [];
    for(let i = 0; i < 20; i++) {
        gems.push({
            id: i, type: GEM_TYPES[Math.floor(Math.random() * GEM_TYPES.length)],
            rx: Math.random() * 0.8 + 0.1, ry: Math.random() * 0.5 + 0.4, status: 'idle' 
        });
    }
    return gems;
}

io.on('connection', (socket) => {
    // 1. 匹配页面：创建/加入房间
    socket.on('createRoom', () => {
        const roomId = Math.floor(1000 + Math.random() * 9000).toString();
        rooms[roomId] = { players: {}, gems: generateGems(), timeLeft: 60, timer: null, matchCount: 1 };
        socket.join(roomId);
        socket.emit('roomCreated', roomId);
    });

    socket.on('joinRoom', (roomId) => {
        if (rooms[roomId] && rooms[roomId].matchCount === 1) {
            rooms[roomId].matchCount = 2;
            socket.join(roomId);
            socket.emit('joinSuccess', roomId);
            io.to(roomId).emit('gameStart', rooms[roomId].gems);
        } else {
            socket.emit('joinFailed', '房间不存在或已满');
        }
    });

    // 2. 游戏页面：玩家重新连入并分配左右位置
    socket.on('rejoinGame', (roomId) => {
        if (rooms[roomId]) {
            socket.join(roomId);
            rooms[roomId].players[socket.id] = { score: 0 };
            
            // 第一个进来的在左边(0)，第二个在右边(1)
            let playerIds = Object.keys(rooms[roomId].players);
            let playerIndex = playerIds.length === 1 ? 0 : 1; 
            socket.emit('playerAssigned', playerIndex);

            // 当两人都连入画面后，开启 60 秒倒计时
            if (playerIds.length === 2 && !rooms[roomId].timer) {
                rooms[roomId].timer = setInterval(() => {
                    rooms[roomId].timeLeft--;
                    io.to(roomId).emit('timeUpdate', rooms[roomId].timeLeft);
                    
                    if (rooms[roomId].timeLeft <= 0) endGame(roomId);
                }, 1000);
            }
        }
    });

    // 3. 实时同步对手的爪子动作
    socket.on('syncHook', ({ roomId, hookData }) => {
        socket.to(roomId).emit('oppoHook', hookData);
    });

    // 4. 矿石交互 (锁定、释放、得分)
    socket.on('lockGem', ({ roomId, gemId }) => {
        if (rooms[roomId] && rooms[roomId].gems[gemId]) {
            rooms[roomId].gems[gemId].status = 'locked';
            io.to(roomId).emit('gemLocked', gemId);
        }
    });

    socket.on('freeGem', ({ roomId, gemId }) => {
        if (rooms[roomId] && rooms[roomId].gems[gemId]) {
            rooms[roomId].gems[gemId].status = 'idle';
            io.to(roomId).emit('gemFreed', gemId);
        }
    });

    socket.on('collectGem', ({ roomId, gemId, value }) => {
        if (rooms[roomId]) {
            if (rooms[roomId].gems[gemId]) rooms[roomId].gems[gemId].status = 'collected';
            if (rooms[roomId].players[socket.id]) rooms[roomId].players[socket.id].score += value;
            
            // 广播比分更新
            io.to(roomId).emit('updateGame', { gemId: gemId, scores: rooms[roomId].players });

            // 矿石挖完也提前结束
            const remaining = rooms[roomId].gems.filter(g => g.status !== 'collected').length;
            if (remaining === 0) endGame(roomId);
        }
    });

    // 5. 结算判定
    function endGame(roomId) {
        if (rooms[roomId]) {
            clearInterval(rooms[roomId].timer);
            let pList = Object.keys(rooms[roomId].players);
            if (pList.length >= 2) {
                let p1Score = rooms[roomId].players[pList[0]].score;
                let p2Score = rooms[roomId].players[pList[1]].score;
                
                let winnerId = null;
                if (p1Score > p2Score) winnerId = pList[0];
                else if (p2Score > p1Score) winnerId = pList[1];
                
                io.to(roomId).emit('gameOver', { winnerId, p1Score, p2Score });
            }
            delete rooms[roomId]; // 销毁房间
        }
    }
});

server.listen(3000, () => console.log('Server running on port 3000'));
