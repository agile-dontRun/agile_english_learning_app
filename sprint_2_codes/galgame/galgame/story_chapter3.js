const chapter3 = [
    {type: 'dialogue', speaker: 'Narration', text: 'You follow the teacher on the elevator to the sixth floor. The elevator door opens, and you see a blue lobby.', bg: '../frontend/assets/scene3/lobby3.jpg'},
    {type: 'dialogue', speaker: "Karen", text: "Welcome to the sixth floor! This is our student activity center, where we host various events and clubs. Let me show you around!"},
    {type: 'transition', images: ['../frontend/assets/scene3/gate.jpg', '../frontend/assets/scene3/corridor3.jpg'], timePerImage: 800},
    {type: 'dialogue', speaker: 'Karen', text: "On the right-hand side is a small sofa where everyone can discuss homework, rest, and more" , bg: '../frontend/assets/scene3/discussing_area.jpg'},
    {type: 'transition', images: ['../frontend/assets/scene3/corridor2.jpg',
    '../frontend/assets/scene3/seminor_room.jpg'],
    timePerImage: 800},
    {type: 'dialogue', speaker: "Karen", text:"On the left side is the seminar room, where we often host lectures and seminars.", bg: '../frontend/assets/scene3/seminor_room.jpg'},
    {type: 'dialogue', speaker: 'Narration', text:'The door of the Seminar room was ajar, revealing a crack that seemed to emit golden light .....', bg:'../frontend/assets/scene3/shining_seminor_room.png'},
    {type: 'explore_choice',
        options:[
            {
                text: "A. Push the door open.",
                subStory:[
                    {type:'dialogue', speaker:"Narration", text:"You suddenly feel a mysterious force, and the blinding light makes you close your eyes."},
                    {type:"dialogue", speaker:"Narration", text:"BOOM! After a strong tremor, you find yourself having traveled through time!"},
                    {type:'dialogue', speaker:"Narration", text:"Congratulations on unlocking the Golden Miner World!"}
                ]
            },
            {text:"B. Do nothing and continue walking forward.",
                subStory:[]
            }   
        ]},
        {type: 'transition', images: ['../frontend/assets/scene3/corridor2.jpg']},
        {type: 'dialogue', speaker:"Karen", text:"Continue walking forward, and you can see a rest area on the left. This is where students chat and have meals.", bg: '../frontend/assets/scene3/rest_area.jpg'},
        {type: 'dialogue', speaker:"Narration", text:"You walk around and see a printer, water fountain, coffee machine, and water dispenser."},
        // {type: 'transition', images: ['../frontend/assets/scene3/printer.jpg',
        // '../frontend/assets/scene3/water_pool.jpg',
        // '../frontend/assets/scene3/coffee_machine.jpg',
        // '../frontend/assets/scene3/water_dispenser.jpg'],
        // timePerImage: 800},
        {type: 'dialogue', speaker:'Narration', text:'You follow the teacher and continue walking. At the end of the hallway, you see a conference room', bg:'../frontend/assets/scene3/classroom_gate.jpg'},
        {type: 'dialogue', speaker: "Karen", text:"This will be the classroom where you will often come in the future, and most of your courses will be conducted here."},
        {type: 'dialogue', speaker: "Narration", text:"You pushed open the door and saw some students studying hard inside, afraid to make a sound, so you quickly came out.", bg:'../frontend/assets/scene3/classroom.jpg'},
        {type: 'transition', images:['../frontend/assets/scene3/corridor4.jpg', '../frontend/assets/scene3/lobby3.jpg'], timePerImage: 800},
        {type: 'dialogue', speaker: "Karen", text:"Alright, that's all for today's visit. It's getting late, and I wish everyone a pleasant learning experience at DIICSU! The cafeteria is not far away, everyone can go eat now!",},

]