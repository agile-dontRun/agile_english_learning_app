const chapter2 = [
   {
    type: 'transition', 
    images:[
        '../frontend/assets/scene2/lobby2.jpg',   // 走廊第一张图
        '../frontend/assets/scene2/corridor.jpg',   // 走廊第二张图
    ],
    timePerImage: 800  // 每张照片停留 800 毫秒 (0.8秒)，你可以调整这个数值控制走路的快慢
    },
    {type: 'dialogue', speaker: "Karen", text: "This is your English Classroom. You'll be having your morning readings and english classes here. So, don't be late!",  bg: "../frontend/assets/scene2/english_classroom.jpg"},
    { type: 'dialogue', speaker: '旁白', text: '你想问问早读具体几点开始。' },
    {type: "choice",
        options:[
            {
                text: "A: 'What time do we start in the morning?' (地道)",
                isCorrect: true,
                response: "Great job! This sentence is authentic spoken English—concise, direct and commonly used by native speakers to ask about morning reading start time, which fits the campus scene perfectly!"
            },
            {
                text: "B: 'What time does the classroom open the door?'",
                isCorrect: false,
                mentorText: "Stop！在英语里询问‘几点开始上课’，极少说 'open the door'，这听起来像是你在问这个物理意义上的门几点解锁。直接问 'What time do we start?' 才最自然！重来！"
            }
        ]
    },
    {type: 'dialogue', speaker: "Karen", text: "Morning reading starts at 8:30 sharp, and your major english classes will usually start at 9:00. So make sure to be on time!"},
    {type: 'transition', images:[
        '../frontend/assets/scene2/corridor.jpg',
        '../frontend/assets/scene2/english_corner.jpg',
    ],
timePerImage: 800},
    {type: 'explore_choice',
        options:[
            {
                text: "A. 看看英语角",
                subStory:[
                    {type:'dialogue', speaker:"Xiaowang", text:"这里放了一盒牌，好像可以玩，（目光看向你)来玩一下吧?", bg: "../frontend/assets/scene2/table.jpg"},
                    {type:"dialogue", speaker:"旁白", text:"系统提示：解锁翻牌小游戏！"},
                ]
            },
            {text:"B. 算了，原地等着。",
                subStory:[]
            }   
        ]
    },
        { type: 'dialogue', speaker: 'Karen', text: "这是我们的一楼，接下来带大家去六楼参观一下.", bg:"../frontend/assets/scene2/english_corner.jpg" },
]