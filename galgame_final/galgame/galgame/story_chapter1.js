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
    { type: 'dialogue', speaker: 'Karen', text: 'Wonderful choice! Watching movies is a great way to pick up daily English. Thank you, Xiangwang. Now, how about you? [Her gaze turned to you, gentle and waiting for your answer] What do you like to do in your free time?'},
    { type: 'choice',
        options:[
            {
                text: "A: I like to do sports every day, I feel very happy when I play basketball.",
                isCorrect: false,
                mentorText: "Bzzzt! Alert! This is a typical \"Chinese flowing sentence\" in English! Although the grammar is not a big mistake, a native speaker would not be so rigid in piecing together sentences. The correct authentic expression should be \"I love doing sports every day - playing basketball makes me really happy.\" It's too rigid, start over!"
            },
            {
                text: "B: My hobby is very good, I often go to the library to read books.",
                isCorrect: false,
                mentorText:"Oh no! You made a mistake of 'Chinese literal translation+logical confusion'! My hobby is very good \"is completely inconsistent with English expression habits - hobbies are not\" good \"or\" bad \", and cannot be described as good; And the connection between the second half and the first half is awkward. If you want to express your love for going to the library to read books in my free time, just say \"I often go to the library to read books in my free time.\" Don't let the teacher misunderstand that you are talking nonsense! Start over!"
            },
            {
                text: "C: I’m really into painting and hanging out with friends—sometimes we practice speaking English together, which I think fits well with this college’s style.",
                isCorrect: true,
                response: "Perfect! Authentic and suitable for the scene! 'be into' is a phrase commonly used by native speakers to express' like ', which is more natural than' like '; The latter half of the sentence mentions practicing English with friends, which is in line with the characteristics of Sino foreign cooperation at Dundee International College. It not only demonstrates hobbies but also reflects expectations for the college."
            }
        ]
    },
    {type: "dialogue", speaker: 'Karen', text: '(Nodding with a smile and giving a thumbs up)That’s fantastic! Painting is a great way to relax, and practicing English with friends is exactly what we encourage here. I’m sure you’ll have a great time in our college.' },
    {type: 'dialogue', speaker: 'Narration', text: 'A few minutes later...' },
    {type: 'dialogue', speaker: 'Karen', text: "Alright everyone, It seems that everyone has arrived. Next, I will take you to visit our school.. Follow me!" },
]