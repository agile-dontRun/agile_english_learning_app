const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();

app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.header(
    'Access-Control-Allow-Headers',
    'Origin, X-Requested-With, Content-Type, Accept, Authorization'
  );
  if (req.method === 'OPTIONS') {
    return res.sendStatus(200);
  }
  next();
});

const server = http.createServer(app);
const io = new Server(server, { cors: { origin: '*' } });

const rooms = {};
const GEM_TYPES = ['oil', 'diamond', 'ruby', 'emerald', 'amethyst', 'gold', 'silver', 'coin'];

function generateGems() {
  const gems = [];
  for (let i = 0; i < 20; i++) {
    gems.push({
      id: i,
      type: GEM_TYPES[Math.floor(Math.random() * GEM_TYPES.length)],
      rx: Math.random() * 0.8 + 0.1,
      ry: Math.random() * 0.5 + 0.4,
      status: 'idle',
    });
  }
  return gems;
}

function createRoomState() {
  return {
    players: {},              // socket.id -> { score, side }
    leftSocketId: null,       // 左玩家 socket
    rightSocketId: null,      // 右玩家 socket
    outfits: {                // 左右玩家穿搭缓存
      left: null,
      right: null,
    },
    gems: generateGems(),
    timeLeft: 60,
    timer: null,
    matchCount: 1,
  };
}

function getPlayerIndexBySocket(room, socketId) {
  if (!room) return null;
  if (room.leftSocketId === socketId) return 0;
  if (room.rightSocketId === socketId) return 1;
  return null;
}

function getPlayerSideBySocket(room, socketId) {
  if (!room) return null;
  if (room.leftSocketId === socketId) return 'left';
  if (room.rightSocketId === socketId) return 'right';
  return null;
}

function emitRoomOutfits(roomId) {
  const room = rooms[roomId];
  if (!room) return;

  io.to(roomId).emit('roomOutfits', {
    left: room.outfits.left,
    right: room.outfits.right,
  });
}

function emitScoreUpdate(roomId) {
  const room = rooms[roomId];
  if (!room) return;

  const leftScore =
    room.leftSocketId && room.players[room.leftSocketId]
      ? room.players[room.leftSocketId].score
      : 0;

  const rightScore =
    room.rightSocketId && room.players[room.rightSocketId]
      ? room.players[room.rightSocketId].score
      : 0;

  io.to(roomId).emit('scoreSync', {
    leftScore,
    rightScore,
    leftSocketId: room.leftSocketId,
    rightSocketId: room.rightSocketId,
  });
}

function tryStartTimer(roomId) {
  const room = rooms[roomId];
  if (!room) return;

  const bothReady = room.leftSocketId && room.rightSocketId;
  if (bothReady && !room.timer) {
    room.timer = setInterval(() => {
      const currentRoom = rooms[roomId];
      if (!currentRoom) {
        clearInterval(room.timer);
        return;
      }

      currentRoom.timeLeft--;
      io.to(roomId).emit('timeUpdate', currentRoom.timeLeft);

      if (currentRoom.timeLeft <= 0) {
        endGame(roomId);
      }
    }, 1000);
  }
}

function cleanupSocketFromRooms(socketId) {
  Object.keys(rooms).forEach((roomId) => {
    const room = rooms[roomId];
    if (!room) return;

    if (room.players[socketId]) {
      delete room.players[socketId];
    }

    if (room.leftSocketId === socketId) {
      room.leftSocketId = null;
    }

    if (room.rightSocketId === socketId) {
      room.rightSocketId = null;
    }
  });
}

function endGame(roomId) {
  const room = rooms[roomId];
  if (!room) return;

  clearInterval(room.timer);

  const leftScore =
    room.leftSocketId && room.players[room.leftSocketId]
      ? room.players[room.leftSocketId].score
      : 0;

  const rightScore =
    room.rightSocketId && room.players[room.rightSocketId]
      ? room.players[room.rightSocketId].score
      : 0;

  let winnerId = null;
  if (leftScore > rightScore) winnerId = room.leftSocketId;
  else if (rightScore > leftScore) winnerId = room.rightSocketId;

  io.to(roomId).emit('gameOver', {
    winnerId,
    p1Score: leftScore,
    p2Score: rightScore,
    leftSocketId: room.leftSocketId,
    rightSocketId: room.rightSocketId,
  });

  delete rooms[roomId];
}

io.on('connection', (socket) => {
  // 1. 匹配页：创建房间
  socket.on('createRoom', () => {
    const roomId = Math.floor(1000 + Math.random() * 9000).toString();
    rooms[roomId] = createRoomState();
    socket.join(roomId);
    socket.emit('roomCreated', roomId);
  });

  // 2. 匹配页：加入房间
  socket.on('joinRoom', (roomId) => {
    const room = rooms[roomId];
    if (room && room.matchCount === 1) {
      room.matchCount = 2;
      socket.join(roomId);
      socket.emit('joinSuccess', roomId);
      io.to(roomId).emit('gameStart', room.gems);
    } else {
      socket.emit('joinFailed', '房间不存在或已满');
    }
  });

  // 3. 游戏页：重连/进入游戏并固定左右位
  socket.on('rejoinGame', (roomId) => {
    const room = rooms[roomId];
    if (!room) return;

    socket.join(roomId);

    let side = getPlayerSideBySocket(room, socket.id);

    if (!side) {
      if (!room.leftSocketId) {
        room.leftSocketId = socket.id;
        side = 'left';
      } else if (!room.rightSocketId) {
        room.rightSocketId = socket.id;
        side = 'right';
      } else {
        // 房间已经满了，不再允许第三个 socket 进入对局
        socket.emit('joinFailed', '游戏房间已满');
        return;
      }
    }

    if (!room.players[socket.id]) {
      room.players[socket.id] = {
        score: 0,
        side,
      };
    } else {
      room.players[socket.id].side = side;
    }

    const playerIndex = side === 'left' ? 0 : 1;
    socket.emit('playerAssigned', playerIndex);

    // 把已有穿搭发给刚进来的这个客户端
    socket.emit('roomOutfits', {
      left: room.outfits.left,
      right: room.outfits.right,
    });

    // 同步当前比分
    emitScoreUpdate(roomId);

    // 两边都就位后再启动计时
    tryStartTimer(roomId);
  });

  // 4. 提交自己的穿搭，服务端缓存并广播
  socket.on('submitOutfit', ({ roomId, side, outfit }) => {
    const room = rooms[roomId];
    if (!room) return;

    const realSide = getPlayerSideBySocket(room, socket.id);
    if (!realSide) return;

    // 以服务端判定为准，忽略前端伪造 side
    room.outfits[realSide] = outfit || null;

    emitRoomOutfits(roomId);
  });

  // 5. 实时同步对手的爪子动作
  socket.on('syncHook', ({ roomId, hookData }) => {
    socket.to(roomId).emit('oppoHook', hookData);
  });

  // 6. 矿石交互
  socket.on('lockGem', ({ roomId, gemId }) => {
    const room = rooms[roomId];
    if (room && room.gems[gemId]) {
      room.gems[gemId].status = 'locked';
      io.to(roomId).emit('gemLocked', gemId);
    }
  });

  socket.on('freeGem', ({ roomId, gemId }) => {
    const room = rooms[roomId];
    if (room && room.gems[gemId]) {
      room.gems[gemId].status = 'idle';
      io.to(roomId).emit('gemFreed', gemId);
    }
  });

  socket.on('collectGem', ({ roomId, gemId, value }) => {
    const room = rooms[roomId];
    if (!room) return;

    if (room.gems[gemId]) {
      room.gems[gemId].status = 'collected';
    }

    if (room.players[socket.id]) {
      room.players[socket.id].score += value;
    }

    io.to(roomId).emit('updateGame', {
      gemId,
      scores: room.players,
    });

    emitScoreUpdate(roomId);

    const remaining = room.gems.filter((g) => g.status !== 'collected').length;
    if (remaining === 0) {
      endGame(roomId);
    }
  });

  socket.on('disconnect', () => {
    cleanupSocketFromRooms(socket.id);
  });
});

server.listen(3000, () => console.log('Server running on port 3000'));
