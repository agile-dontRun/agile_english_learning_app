const chapter5 =[
    {
    type: 'dialogue', speaker:"旁白", text:" 经过一上午的‘地狱式’参观，你已经饿得前胸贴后背了。现在是学校食堂的高峰期，你需要搞定两件事：一是找那位语速极快的食堂阿姨点一份午餐，二是去和刚认识的同学小王（Wang）汇合，开启你的校园社交第一步。", bg: "../frontend/assets/scene5/canteen.jpg"
    },
    {
    type: 'transition', 
    images:[
        '../frontend/assets/scene5/canteen.jpg',   // 走廊第一张图
        '../frontend/assets/scene5/canteenLobby.jpg',   // 走廊第二张图
    ],
    timePerImage: 800  // 每张照片停留 800 毫秒 (0.8秒)，你可以调整这个数值控制走路的快慢
    },
    {type:"dialogue", speaker:"canteen server", text:"喧闹的食堂，蒸汽升腾，食堂阿姨挥舞着大勺，面无表情地看着你"},
    {type:"dialogue", speaker:"canteen server", text:"Next! What do you want, hon?"},
    {type:"choice",
        options:[
            {text:"A: Give me the chicken and rice. Fast, I'm hungry.",
                isCorrect: false,
                mentorText: " 兄弟，食堂阿姨惹不起！这种命令口吻（Give me...）在任何国家都是不受欢迎的。即便对方是服务人员，你的礼貌也是你的名片。"
            },
            {
                text:"B: Hi! Could I get the chicken and rice, please?",
                isCorrect: true,
                response: " 漂亮！\"Could I get...\" 永远是点餐时的万能礼貌句型，哪怕在繁忙的食堂，保持友好的态度（Hi / Please）也会让你收获更好的服务。"
            },
            {
                text:"C: I want this, this, and this.",
                isCorrect: false,
                mentorText: "指手画脚虽然能完成任务，但显得很没自信。试着说出菜名，比如 \"The curry chicken\" 或者 \"The stir-fried beef\"，这能帮你快速提升英语自信。"
            }
        ]
    },
    {type:"dialogue", speaker:"canteen server", text:"阿姨迅速地把菜盛好，递给你。"},
    {type: "dialogue", speaker:"旁白", text:"你拿着餐盘，寻找小王的身影，并端着盘子朝她走过去。"},
    {type:"dialogue", speaker:"Xiaowang", text:"Hey! Over here!Did you have fun in the tour?", bg: "../frontend/assets/scene5/canteenTable.jpg"},
    {type:"choice", options:[
        {
            text: "A:It's so-so. The building is big.", 
            isCorrect: false, 
            mentorText:'\"So-so\" 是中式英语的典型产物，虽然没错，但听起来很敷衍。如果没兴趣，可以说 \"It was a bit long, but interesting.\"'},
        {
            text: "B: Yeah, it was pretty cool! But I'm exhausted.",
            isCorrect: true,
            response: "太地道了！\"Pretty cool\" 是非常自然的口语修饰词。再加上 \"I\'m exhausted\"（我精疲力竭），瞬间就和同学产生了情感共鸣。"
        },
        {
            text:"C: I am very tired. Very very tired.",
            isCorrect: false,
            mentorText:"只有\"很很很\"，词汇太贫乏啦！记住，少用 Very，多用高级形容词（如 exhausted, fascinating, overwhelming），会让你的表达听起来更有深度。"
        }
        ]
    },
    {type: "dialogue", speaker:"Xiaowang", text:"So, are you planning to join any clubs or societies?"},
    {type:"choice", 
        options:[
            {
                text: "A: I don't know. Maybe.",
                isCorrect: false,
                mentorText: "这是一个标准的“聊天终结者”。如果你想交朋友，千万不要用“也许/不知道”来回复，哪怕随便编一个兴趣爱好，对话也能继续下去。"
            },
            {
                text: "B: Not sure yet. Maybe the photography club. How about you?",
                isCorrect: true,
                response: "满分！回答一个问题，同时反问对方（How about you?），这是保持对话流动的黄金法则。"
            },
            {
                text: "C: I want to study hard. No club.",
                isCorrect: false,
                mentorText: "(太沉重啦！）我们不是在苦行。适当展示一点课余爱好，会让你的形象更立体，而不只是个只会读书的学霸。"
            }
        ]
    },
    {type: "dialogue", speaker: "Xiaowang", text:"That's awesome! I'm thinking about the basketball team."},
    {type: "dialogue", speaker:"旁白", text:"You and Xiao Wang continued to chat a lot and had a great time together..."},
    {type: "dialogue", speaker:"旁白", text:"“今天你学会了：食堂生存守则：礼貌第一，勺子里的肉才会多！社交流动法则：拒绝只用 'Yes/No'，通过 'How about you?' 延长对话生命线。摆脱贫乏：用 exhausted 替代 very tired，用 pretty cool 替代 interesting。干得漂亮！现在的你，看起来像个真正的大一新生了。"}
]