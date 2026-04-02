const chapter4 =[
    {type: 'dialogue', speaker: 'Narration', text: 'You walked into a coffee shop on campus and wanted to order a cup of coffee to refresh yourself.'},
    {type: 'dialogue', speaker: 'barista', text: "(With a smile on her face) Hi there! How's it going today?" },
    {type: 'choice',
        options :[
            {
                text: "A: I am fine, thank you, and you?",
                isCorrect: false,
                mentorText:"Stop reciting junior high school English textbooks quickly! In real daily communication, almost no one would use the standard phrase 'robot reply'. It sounds too formal and stiff."
            },
            {
                text: "B: Give me coffee, please.",
                isCorrect: false,
                mentorText:"Too rude! English-speaking countries highly value daily politeness (Politeness). Using the imperative sentence \"Give me...\" sounds like robbing the coffee shop."
            },
            {
                text:"C: Good, thanks! How are you?",
                isCorrect: true,
                response: "Perfect! \"Good, thanks\" or \"Not bad\" is the most authentic and relaxed response, and asking \"How are you?\" in return instantly closes the gap between you."
            }
        ]      
    },
    {type:"dialogue", speaker: 'barista', text: "I'm doing great, thanks for asking! What can I get started for you today?" },
    {type: 'choice',
        options:[
            {
                text: "A: I want a latte.",
                isCorrect: false,
                mentorText:"Although the grammar is correct, 'I want' sounds a bit like a child asking for candy, slightly stiff."
            },
            {
                text:"B: Can I get a large latte with oat milk, please?",
                isCorrect: true,
                response: "Full marks sentence structure! The immortal sentence pattern for ordering is always \"Can I get a...\" or \"I\'ll have a...\". Adding \'please\' is even more a reflection of one\'s character. You even clearly stated the size and oat milk at once, which is extremely efficient!"
            },
            {
                text: "C: Please give me one big coffee, no cow milk. ",
                isCorrect: false,
                mentorText: " \"Big coffee\" can be barely understood, but what the hell is' no cow milk '? Just mention the names of alternative milk, such as oat milk, soy milk, and almond milk."
            }
        ]
    },
    {type: "dialogue", speaker: 'barista', text: "Is that for here or to go?"},
    {type: 'choice', 
        options:[
            {
                text: 'A: Take out, please.',
                isCorrect: false,
                mentorText:"\"Take out\" is usually used in Chinese restaurants and pizza shops to order takeout and take home food. For coffee or fast food, this is usually not the case."
            },
            {
                text: "B: To go, please.",
                isCorrect: true,
                response: "Perfect!\"To go \"is the most commonly used expression in North America (USA, Canada). Remember this phrase, you won't be afraid to travel all over America."
            },
            {
                text: "C: Take away.",
                isCorrect: true,
                response: "Correct! If you're setting in the UK, Australia or New Zealand, \"Take away\" is the most authentic way to say it. Flexibly switch based on your study destination!"
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
    {type: 'dialogue', speaker: "Narration", text: "You waited at the counter for a bit..."},
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
    {type: "dialogue", speaker: "Narration", text:"Congratulations on completing the coffee shop trial! Let's review today's golden phrases: 1. Greeting: Good, thanks! How are you? 2. Ordering magic: Can I get a... / I'll have a... 3. Introducing yourself: It's [Name]. 4. Farewell response: You too!"}
]