const chapter3 = [
    {type: 'dialogue', speaker: '旁白', text: '你跟着老师坐电梯来到六楼，电梯门打开，你看到了一个蓝色的大厅。', bg: '../frontend/assets/scene3/lobby3.jpg'},
    {type: 'dialogue', speaker: "Karen", text: "Welcome to the sixth floor! This is our student activity center, where we host various events and clubs. Let me show you around!"},
    {type: 'transition', images: ['../frontend/assets/scene3/gate.jpg', '../frontend/assets/scene3/corridor3.jpg'], timePerImage: 800},
    {type: 'dialogue', speaker: 'Karen', text: "右手边是一个小沙发，大家可以在这里讨论作业，休息等等" , bg: '../frontend/assets/scene3/discussing_area.jpg'},
    {type: 'transition', images: ['../frontend/assets/scene3/corridor2.jpg',
    '../frontend/assets/scene3/seminor_room.jpg'],
    timePerImage: 800},
    {type: 'dialogue', speaker: "Karen", text:"左手边是seminor room，我们经常在这里举办讲座和研讨会。", bg: '../frontend/assets/scene3/seminor_room.jpg'},
    {type: 'dialogue', speaker: '旁白', text:'seminor room的门虚掩着露出一个缝，里面似乎冒着金光......', bg:'../frontend/assets/scene3/shining_seminor_room.png'},
    {type: 'explore_choice',
        options:[
            {
                text: "A. 推开门",
                subStory:[
                    {type:'dialogue', speaker:"旁白", text:"你突然感受到了一股神秘的力量，刺眼的光让你闭上眼睛"},
                    {type:"dialogue", speaker:"旁白", text:"轰隆隆！一阵强烈的震动过后，你发现自己竟然穿越了！"},
                    {type:'dialogue', speaker:"旁白", text:"恭喜解锁黄金矿工世界！"}
                ]
            },
            {text:"B. 什么都不做，继续往前走。",
                subStory:[]
            }   
        ]},
        {type: 'transition', images: ['../frontend/assets/scene3/corridor2.jpg']},
        {type: 'dialogue', speaker:"Karen", text:"继续往前走，左边可以看到是一个休息区，这里是学生聊天，吃饭的地方。", bg: '../frontend/assets/scene3/rest_area.jpg'},
        {type: 'dialogue', speaker:"旁白", text:"你在这里四处走了走，看到这里有printer, water pool, coffee machine and water dispenser."},
        {type: 'transition', images: ['../frontend/assets/scene3/printer.jpg',
        '../frontend/assets/scene3/water_pool.jpg',
        '../frontend/assets/scene3/coffee_machine.jpg',
        '../frontend/assets/scene3/water_dispenser.jpg'],
        timePerImage: 800},
        {type: 'dialogue', speaker:'旁白', text:'你跟着老师继续往前走，在走廊尽头看到了一间会议室', bg:'../frontend/assets/scene3/classroom_gate.jpg'},
        {type: 'dialogue', speaker: "Karen", text:"这里就将是你们今后经常来的教室啦， 你们的课程大部分都会在这里开展。"},
        {type: 'dialogue', speaker: "旁白", text:"你们推开门，看到有学生在里面专心致志地自习,不敢出声，赶紧出来了。", bg:'../frontend/assets/scene3/classroom.jpg'},
        {type: 'transition', images:['../frontend/assets/scene3/corridor4.jpg', '../frontend/assets/scene3/lobby3.jpg'], timePerImage: 800},
        {type: 'dialogue', speaker: "Karen", text:"好了，今天的参观就到这里了，时间不早了，祝大家在DIICSU有一个愉快的学习生活！食堂就在不远处，大家可以去吃饭了！",},

]