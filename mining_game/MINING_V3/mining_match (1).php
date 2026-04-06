<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>联机匹配 - 挖矿学单词</title>
    <!-- 引入 Socket.io 客户端 -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <style>
        body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; background: #2c3e50; color: white; font-family: 'Microsoft YaHei', sans-serif;}
        .match-box { background: #a1784f; border: 6px solid #5a3c26; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5);}
        input { padding: 10px; font-size: 20px; text-align: center; margin: 10px 0; width: 200px; border-radius: 5px; border: 2px solid #333; }
        .btn { padding: 15px 30px; font-size: 20px; cursor: pointer; background: #f9d860; border: 3px solid #5a3c26; font-weight: bold; border-radius: 10px; margin: 10px; transition: 0.2s;}
        .btn:hover { background: #ffeb99; transform: scale(1.05); }
    </style>
</head>
<body>
    <button class="btn" style="position:absolute; top:20px; left:20px; padding:10px 20px;" onclick="location.href='mining_index.php'">◀ 返回大厅</button>
    
    <div class="match-box">
        <h1 style="margin-top:0;">双人联机对战</h1>
        <p>所有宝石概率相等，得分高者奖励 300 金币！</p>
        
        <div style="margin-top: 30px;">
            <button class="btn" onclick="createRoom()">👑 创建房间</button>
            <br><br>
            <input type="text" id="roomInput" placeholder="输入4位房间号">
            <br>
            <button class="btn" onclick="joinRoom()">🔗 加入房间</button>
        </div>
        
        <h2 id="status-msg" style="color:#e74c3c; margin-top:20px; text-shadow: 1px 1px 0 #fff;"></h2>
    </div>

    <script>
        // ⚠️ 极其重要：把这里的 IP 换成你阿里云的公网 IP！
        const socket = io('http://8.162.9.154:3000');
        const statusMsg = document.getElementById('status-msg');

        function createRoom() {
            socket.emit('createRoom');
            statusMsg.innerText = "正在创建房间...";
        }

        function joinRoom() {
            const rid = document.getElementById('roomInput').value;
            if(rid.length === 4) {
                socket.emit('joinRoom', rid);
                statusMsg.innerText = "正在连接...";
            } else {
                alert("请输入4位数字房间号");
            }
        }

        socket.on('roomCreated', (roomId) => {
            statusMsg.innerText = `房间号: ${roomId} ，等待对手加入...`;
            sessionStorage.setItem('matchRoomId', roomId);
            sessionStorage.setItem('mySide', 'left');
        });

        socket.on('joinSuccess', (roomId) => {
            sessionStorage.setItem('matchRoomId', roomId);
            sessionStorage.setItem('mySide', 'right');
        });

        socket.on('joinFailed', (msg) => { 
            statusMsg.innerText = msg; 
        });

        // 当人满时，服务器下发 gameStart 及矿石数据
        socket.on('gameStart', (gemsData) => {
            statusMsg.innerText = "匹配成功！正在进入矿区...";
            sessionStorage.setItem('sharedGems', JSON.stringify(gemsData));
        
            setTimeout(() => {
                window.location.href = 'mining_double.php'; 
            }, 1000);
        });
    </script>
</body>
</html>
