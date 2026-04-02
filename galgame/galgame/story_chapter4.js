const chapter4 =[
    {type: 'dialogue', speaker: '旁白', text: '你走进了校园里的一家咖啡店，想要点一杯咖啡提提神。'},
    {type: 'dialogue', speaker: 'barista', text: "(面带微笑)Hi there! How's it going today?" },
    {type: 'choice',
        options :[
            {
                text: "A: I am fine, thank you, and you?",
                isCorrect: false,
                mentorText:"快停止背诵初中英语课本！在真实的日常交流中，几乎没有人会用这句标准的“机器人回复”。它听起来太正式且生硬了。"
            },
            {
                text: "B: Give me coffee, please.",
                isCorrect: false,
                mentorText:"太粗鲁了！英语国家非常看重日常礼貌（Politeness）。直接用祈使句 \"Give me...\" 听起来像是在抢劫咖啡店。"
            },
            {
                text:"C: Good, thanks! How are you?",
                isCorrect: true,
                response: "完美！\"Good, thanks\" 或者 \"Not bad\" 是最地道、最放松的回答，同时反问一句 \"How are you?\" 能瞬间拉近距离。"
            }
        ]      
    },
    {type:"dialogue", speaker: 'barista', text: "I'm doing great, thanks for asking! What can I get started for you today?" },
    {type: 'choice',
        options:[
            {
                text: "A: I want a latte.",
                isCorrect: false,
                mentorText:"虽然语法没错，但 \"I want\" 听起来稍微有点像小孩子在要糖果，略显生硬。"
            },
            {
                text:"B: Can I get a large latte with oat milk, please?",
                isCorrect: true,
                response: "满分句型！点餐的神仙句型永远是 \"Can I get a...\" 或者 \"I'll have a...\"。加上 \"please\" 更是素质体现。你甚至一次性把大小（large）和奶类（oat milk）都说清楚了，效率极高！"
            },
            {
                text: "C: Please give me one big coffee, no cow milk. ",
                isCorrect: false,
                mentorText: " \"Big coffee\" 勉强能听懂，但 \"no cow milk\"（不要牛奶）是什么鬼？直接说替代奶的名字就好啦，比如 oat milk（燕麦奶）, soy milk（豆奶）, almond milk（杏仁奶）。"
            }
        ]
    },
    {type: "dialogue", speaker: 'barista', text: "Is that for here or to go?"},
    {type: 'choice', 
        options:[
            {
                text: 'A: Take out, please.',
                isCorrect: false,
                mentorText:"\"Take out\" 通常用于中餐馆、披萨店点外卖带回家的食物。对于咖啡或者快餐，通常不这么说。"
            },
            {
                text: "B: To go, please.",
                isCorrect: true,
                response: "标答！\"To go\" 是北美（美、加）最常用的表达。记住这个短语，走遍美国都不怕。"
            },
            {
                text: "C: Take away.",
                isCorrect: true,
                response: "正确！如果你设定在英国、澳大利亚或新西兰，\"Take away\" 是最地道的说法。根据你的留学目的地灵活切换吧！"
            }
        ]
    },
    {type: "dialogue", speaker: 'barista', text: "Alright, can I get a name for the order? And that'll be $5.50." },
    {type: "choice", 
        options: [
            {
                text: "A: My name is Li Ming. Sweep you or you sweep me?",
                isCorrect: false,
                mentorText: "哈哈哈哈！中国留学生经典名场面！\"Sweep\" 是用扫帚扫地，绝对不能用来翻译“扫码”！国外一般没有支付宝/微信的扫码支付。如果是扫条形码，那是 \"Scan\"。但买咖啡直接说刷卡（pay by card）或 Apple Pay 就行了。"
            },
            {
                text: "B: It's Li Ming. I'll pay by card.",
                isCorrect: true,
                response:"非常自然！报名字时用 \"It's [名字]\" 比 \"My name is [名字]\" 要口语化得多。"
            },
            {
                text: "C: I am Li Ming. Here is money.",
                isCorrect: false,
                mentorText: "\"Here is the money\" 听起来像黑帮在进行非法交易。如果给现金，直接递过去说 \"Here you go\" 就行啦。"
            }
        ]
    },
    {type: 'dialogue', speaker: "旁白", text: "你在出杯台等了一会......"},
    {type: "dialogue", speaker: "barista", text:"There you go! Have a good one!" },
    {type: "choice", 
        options: [
            {
                text:"（低着头，拿了咖啡一言不发飞速逃离现场。）",
                isCorrect: false,
                mentorText:"社恐犯了是不是？但一声不吭拿了就跑非常不礼貌哦。勇敢点，眼神交流是自信的开始！"
            },
            {
                text:"You too! Thanks!",
                isCorrect: true,
                response: "通关大吉！别人祝你 \"Have a good one / Have a nice day\"，最完美、最下意识的回复就是 \"You too! (你也是)\"。再加上一句 Thanks，你已经是个完美的 local (本地人) 了！"
            },
            {
                text: "Bye, bye.",
                isCorrect: true,
                response: "\"Bye bye\" 稍微有点幼稚，通常是小孩子或者情侣之间说得多。成年人之间用 \"Bye\", \"See ya\" 或者 \"You too\" 会更加成熟自然."
            }
        ]
    },
    {type: "dialogue", speaker: "旁白", text:"恭喜你完成了咖啡店的试炼！让我们复习一下今天的黄金句型：1. 打招呼：Good, thanks! How are you? 2.点单神器：Can I get a... / I'll have a... 3.报名字：It's [Name]. 4.告别神回复：You too!"}
]