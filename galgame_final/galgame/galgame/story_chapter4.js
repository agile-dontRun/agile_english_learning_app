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
                mentorText: "Ha ha ha ha! Classic scene of Chinese international students \'Sweep' is used to sweep the floor with a broom and should never be translated as' scan code '! Generally, there is no code scanning payment of Alipay/WeChat abroad. If it's scanning a barcode, then it's' Scan '. But just say pay by card or Apple Pay when buying coffee."
            },
            {
                text: "B: It's Li Ming. I'll pay by card.",
                isCorrect: true,
                response:"Very natural! When introducing your name, using \"It's [Name]\" is much more conversational than \"My name is [Name]\"."
            },
            {
                text: "C: I am Li Ming. Here is money.",
                isCorrect: false,
                mentorText: "\"Here is the money\" sounds like a gangster conducting an illegal transaction. If giving cash, just hand it over and say \"Here you go\"."
            }
        ]
    },
    {type: 'dialogue', speaker: "Narration", text: "You waited at the counter for a bit..."},
    {type: "dialogue", speaker: "barista", text:"There you go! Have a good one!" },
    {type: "choice", 
        options: [
            {
                text:"（With his head down, he grabbed his coffee and quickly fled the scene without saying a word",
                isCorrect: false,
                mentorText:"Have you committed social anxiety? But it's very impolite to run away without saying a word. Be brave, eye contact is the beginning of confidence!"
            },
            {
                text:"You too! Thanks!",
                isCorrect: true,
                response: "Good luck in clearing customs! Others wish you 'Have a good one/Have a nice day', and the most perfect and subconscious response is' You too! 'Adding a thank you, you are already a perfect local!"
            },
            {
                text: "Bye, bye.",
                isCorrect: true,
                response: "\'bye bye' is a bit childish, usually used by children or couples who talk too much. Using 'bye', 'see ya' or 'you too' among adults will be more mature and natural."
            }
        ]
    },
    {type: "dialogue", speaker: "Narration", text:"Congratulations on completing the coffee shop trial! Let's review today's golden phrases: 1. Greeting: Good, thanks! How are you? 2. Ordering magic: Can I get a... / I'll have a... 3. Introducing yourself: It's [Name]. 4. Farewell response: You too!"}
]