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
    { type: 'dialogue', speaker: 'Narration', text: 'Do you want to ask specifically when morning reading starts.' },
    {type: "choice",
        options:[
            {
                text: "A: 'What time do we start in the morning?'",
                isCorrect: true,
                response: "Great job! This sentence is authentic spoken English—concise, direct and commonly used by native speakers to ask about morning reading start time, which fits the campus scene perfectly!"
            },
            {
                text: "B: 'What time does the classroom open the door?'",
                isCorrect: false,
                mentorText: "Stop！In English, when asking 'what time does class start', it is rare to say 'open the door', which sounds like you are asking what time the physical door is unlocked. What time do we start? It's the most natural! Start over!"
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
                text: "A. Take a look at the English corner",
                subStory:[
                    {type:'dialogue', speaker:"Xiaowang", text:"Here's a box of cards, it seems like they can be played. (Looking at you) Can you play with them?", bg: "../frontend/assets/scene2/table.jpg"},
                    {type:"dialogue", speaker:"Narration", text:"System prompt: Unlock the flip card game!"},
                ]
            },
            {text:"B. Never mind, let's just wait here.",
                subStory:[]
            }   
        ]
    },
        { type: 'dialogue', speaker: 'Karen', text: "这是我们的一楼，接下来带大家去六楼参观一下.", bg:"../frontend/assets/scene2/english_corner.jpg" },
]