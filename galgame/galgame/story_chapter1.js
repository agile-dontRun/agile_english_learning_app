const chapter1 = [
    { type: 'dialogue', speaker: 'Narration', text: 'Today is Campus Open Day, the first day of school. Although I have memorized tens of thousands of words, when facing an all English environment, my palms still sweat profusely……', bg: "../frontend/assets/scene1/lobby1.jpg" },
    { type: 'dialogue', speaker: 'Karen', text: "Hey everyone! Welcome to the university! I'm Mr. Davis. You guys must be the fresh blood. How's everyone doing today?" },
    { type: 'dialogue', speaker: 'System Prompt', text: 'Karen suddenly looks at you, and everyone\'s attention is focused on you. Please answer!' },
    { 
        type: 'choice',
        options:[
            {
                text: "A: 'Pretty good! Excited to be here.'",
                isCorrect: true,
                response: "That's the spirit! ",
                //score: 5
            },
            {
                text: "B: 'I am fine, thank you, and you?'",
                isCorrect: false,
                mentorText: "Bzzzt! Alert! Unless you have just been rescued from the hospital, no one will say 'I am fine' like a robot. Just say 'Pretty good' directly! Start over!"
            },
            {
                text: "C: 'My mood is very high today!'",
                isCorrect: false,
                mentorText: "Oh my god! 'High' Describing a state usually refers to you taking drugs! To express a good mood, use 'Excited'. If you don't want to be taken away by security, please retract this sentence immediately!"
            }
        ]
    },
    //{ type: 'dialogue', speaker: 'Narration', text: '（场景一顺利通过，你松了一口气，跟着队伍继续往前走……）' },
    { type: 'dialogue', speaker: 'Karen', text: "I love your energy. Next up, let's talk about your hobbies! What do you like to do in your free time? Who wants to be the first to share?" },
    { type: 'dialogue', speaker: 'Xiaowang', text: "Hi Karen, I'm Xiangwang. In my free time, I like to watch English movies and read international news. I hope I can improve my English and learn more about different cultures here, since it's an international college."},
    { type: 'dialogue', speaker: 'Karen', text: 'Wonderful choice! Watching movies is a great way to pick up daily English. Thank you, Xiangwang. Now, how about you? 【他的目光转向你，眼神温和，等待你的回答】What do you like to do in your free time?'},
    { type: 'choice',
        options:[
            {
                text: "A: I like to do sports every day, I feel very happy when I play basketball.",
                isCorrect: false,
                mentorText: "Bzzzt! 警报！这是典型的“中式流水句”英语！虽然语法没大错，但 native speaker 不会这么生硬地拼接句子。正确的地道表达应该是 “I love doing sports every day—playing basketball makes me really happy.” 太刻板啦，重来！"
            },
            {
                text: "B: My hobby is very good, I often go to the library to read books.",
                isCorrect: false,
                mentorText:"Oh no! 你犯了“中式直译+逻辑混乱”的错误！“My hobby is very good” 完全不符合英文表达习惯——爱好没有“好坏”之分，不能用 good 形容；而且后半句和前半句衔接生硬。想表达喜欢去图书馆看书，直接说 “I often go to the library to read books in my free time.” 就好，别让老师误会你在说废话哦！重来！"
            },
            {
                text: "C: I’m really into painting and hanging out with friends—sometimes we practice speaking English together, which I think fits well with this college’s style.",
                isCorrect: true,
                response: "Perfect! 地道又贴合场景！“be into” 是 native speaker 常用的表达“喜欢”的短语，比 “like” 更自然；后半句提到和朋友练习英语，贴合邓迪国际学院的中外合作特点，既展示了爱好，又体现了对学院的期待。"
            }
        ]
    },
    {type: "dialogue", speaker: 'Karen', text: '(笑着点头，竖起大拇指）That’s fantastic! Painting is a great way to relax, and practicing English with friends is exactly what we encourage here. I’m sure you’ll have a great time in our college.' },
    {type: 'dialogue', speaker: 'Narration', text: 'A few minutes later...' },
    {type: 'dialogue', speaker: 'Karen', text: "Alright everyone, It seems that everyone has arrived. Next, I will take you to visit our school.. Follow me!" },
]