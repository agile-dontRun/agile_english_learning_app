const chapter5 =[
    {
    type: 'dialogue', speaker:"Narration", text:"After a whole morning of this 'hell-like' campus tour, you're starving. It is now the busiest time in the school cafeteria, and you need to accomplish two things: first, order a lunch from the cafeteria auntie who speaks extremely fast; second, meet up with your newly acquainted classmate Wang and take your first step into campus social life.", bg: "../frontend/assets/scene5/canteen.jpg"
    },
    {
    type: 'transition', 
    images:[
        '../frontend/assets/scene5/canteen.jpg',
        '../frontend/assets/scene5/canteenLobby.jpg',   
    ],
    timePerImage: 800  
    },
    {type:"dialogue", speaker:"canteen server", text:"In the noisy cafeteria, steam rises everywhere, and the cafeteria auntie waves her big ladle while looking at you expressionlessly."},
    {type:"dialogue", speaker:"canteen server", text:"Next! What do you want, hon?"},
    {type:"choice",
        options:[
            {text:"A: Give me the chicken and rice. Fast, I'm hungry.",
                isCorrect: false,
                mentorText: "Brother, you really shouldn't provoke the cafeteria auntie! This commanding tone (Give me...) is not welcomed in any country. Even when talking to service staff, politeness is still your personal calling card."
            },
            {
                text:"B: Hi! Could I get the chicken and rice, please?",
                isCorrect: true,
                response: "\"Could I get...\" is always a universally polite sentence pattern when ordering food. Even in a busy cafeteria, keeping a friendly attitude (Hi/Please) will usually get you better service."
},
            {
                text:"C: I want this, this, and this.",
                isCorrect: false,
                mentorText: "Although pointing can get the job done, it makes you seem lacking in confidence. Try saying the dish names instead, such as 'the curry chicken' or 'the stir-fried beef.' This can also help you build your confidence in speaking English."
            }
        ]
    },
    {type:"dialogue", speaker:"canteen server", text:"The auntie quickly prepared the dishes and handed them to you."},
    {type: "dialogue", speaker:"Narration", text:"Holding your tray, you look around for Wang and walk toward her."},
    {type:"dialogue", speaker:"Xiaowang", text:"Hey! Over here! Did you have fun on the tour?", bg: "../frontend/assets/scene5/canteenTable.jpg"},
    {type:"choice", options:[
        {
            text: "A: It's so-so. The building is big.", 
            isCorrect: false, 
            mentorText:"\"So-so\" is a typical Chinglish expression. Although it is not technically wrong, it sounds rather dismissive. If you are not that interested, you could say \"It was a bit long, but interesting.\""
        },
        {
            text: "B: Yeah, it was pretty cool! But I'm exhausted.",
            isCorrect: true,
            response: "That sounds super natural! \"Pretty cool\" is a very common and natural spoken expression. Adding \"I'm exhausted\" also creates instant emotional resonance with your classmate."
        },
        {
            text:"C: I am very tired. Very very tired.",
            isCorrect: false,
            mentorText:"Using only \"very, very, very\" makes your vocabulary sound too limited. Remember: use fewer \"very\"s and more advanced adjectives like exhausted, fascinating, or overwhelming to make your English sound richer and more natural."
        }
        ]
    },
    {type: "dialogue", speaker:"Xiaowang", text:"So, are you planning to join any clubs or societies?"},
    {type:"choice", 
        options:[
            {
                text: "A: I don't know. Maybe.",
                isCorrect: false,
                mentorText: "This is a classic conversation killer. If you want to make friends, try not to answer with only \"maybe\" or \"I don't know.\" Even casually mentioning one possible interest can help keep the conversation going."
            },
            {
                text: "B: Not sure yet. Maybe the photography club. How about you?",
                isCorrect: true,
                response: "Perfect! You answered the question and then asked one back (How about you?), which is the golden rule for keeping a conversation flowing."
            },
            {
                text: "C: I want to study hard. No club.",
                isCorrect: false,
                mentorText: "That sounds a bit too heavy! University life is not meant to be all suffering. Showing a little interest in extracurricular activities makes you seem more well-rounded, not just like a study machine."
            }
        ]
    },
    {type: "dialogue", speaker: "Xiaowang", text:"That's awesome! I'm thinking about the basketball team."},
    {type: "dialogue", speaker:"Narration", text:"You and Wang kept chatting and had a great time together..."},
    {type: "dialogue", speaker:"Narration", text:"\"Today you learned: Cafeteria survival rule: politeness comes first, and you might even get more meat in your spoon! Social flow rule: avoid replying with only 'Yes/No'—use 'How about you?' to keep the conversation alive. Escape basic vocabulary: use exhausted instead of very tired, and pretty cool instead of interesting. Great job! Now you really look like a genuine freshman.\""}
]