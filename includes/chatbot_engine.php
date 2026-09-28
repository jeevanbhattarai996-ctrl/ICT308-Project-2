<?php
function getChatbotResponse(string $message): array
{
    $message = strtolower(trim($message));

    $emergencyKeywords = [
        'chest pain','cannot breathe',"can't breathe",'difficulty breathing',
        'unconscious','severe bleeding','heart attack','stroke','suicide',
        'overdose','seizure'
    ];

    foreach ($emergencyKeywords as $keyword) {
        if (str_contains($message, $keyword)) {
            return [
                'message'=>'This may be a medical emergency. Please contact emergency services or go to the nearest emergency department immediately. You can also use the Emergency SOS feature in CarePlus.',
                'type'=>'emergency',
                'link'=>'emergency_sos.php',
                'link_text'=>'Open Emergency SOS'
            ];
        }
    }

    $responses = [
        [
            'keywords'=>['hello','hi','hey','good morning','good afternoon'],
            'message'=>'Hello! I am the CarePlus Assistant. I can help you with appointments, doctors, health records, medicine reminders, video consultations and other CarePlus features.'
        ],
        [
            'keywords'=>['appointment','book','booking'],
            'message'=>'You can book an appointment with a doctor through the appointment booking page. You can select an available doctor and appointment details.',
            'link'=>'book_appointment.php',
            'link_text'=>'Book Appointment'
        ],
        [
            'keywords'=>['doctor','specialist','specialisation'],
            'message'=>'CarePlus can help you find the appropriate doctor. If you are unsure which specialist you need, you can use the Symptom Checker for a basic recommendation.',
            'link'=>'symptom_checker.php',
            'link_text'=>'Open Symptom Checker'
        ],
        [
            'keywords'=>['symptom','sick','feeling unwell','condition'],
            'message'=>'You can use the AI Symptom Checker to select your symptoms and view possible condition matches and a recommended doctor. It is a rule-based estimate and does not provide a medical diagnosis.',
            'link'=>'symptom_checker.php',
            'link_text'=>'Check Symptoms'
        ],
        [
            'keywords'=>['health dashboard','vitals','blood pressure','blood sugar','heart rate','weight','steps'],
            'message'=>'The Health Dashboard allows you to record and view health information such as blood pressure, heart rate, blood sugar, weight and steps.',
            'link'=>'health_dashboard.php',
            'link_text'=>'Open Health Dashboard'
        ],
        [
            'keywords'=>['medicine','medication','reminder','tablet','dose'],
            'message'=>'The Smart Medicine Reminder lets you record your medicines and reminder times so you can keep track of your medication schedule.',
            'link'=>'medicine_reminder.php',
            'link_text'=>'Medicine Reminder'
        ],
        [
            'keywords'=>['ehr','medical record','medical report','prescription','x-ray','vaccination','allergy'],
            'message'=>'Electronic Health Records allow you to manage supported medical information and documents such as reports, prescriptions, vaccinations and allergies.',
            'link'=>'ehr.php',
            'link_text'=>'View Health Records'
        ],
        [
            'keywords'=>['video','video consultation','online consultation','call doctor'],
            'message'=>'Video Consultation allows supported patients and doctors to communicate through their browsers using WebRTC. Camera and microphone permission will be required.',
            'link'=>'video_consultation.php',
            'link_text'=>'Video Consultation'
        ],
        [
            'keywords'=>['risk','diabetes','heart disease','high blood pressure','risk prediction'],
            'message'=>'The Disease Risk Prediction feature uses predefined scoring rules to estimate Low, Medium or High risk for supported conditions. It is not a medical diagnosis.',
            'link'=>'risk_prediction.php',
            'link_text'=>'Check Health Risk'
        ],
        [
            'keywords'=>['qr','medical card','medical id','qr card'],
            'message'=>'Your QR Medical Card provides selected emergency information such as your blood group, allergies and emergency contact details.',
            'link'=>'qr_medical_card.php',
            'link_text'=>'View QR Medical Card'
        ],
        [
            'keywords'=>['sos','emergency','help'],
            'message'=>'If you need urgent assistance, you can use the Emergency SOS feature to send an alert to the hospital. For a life-threatening emergency, contact emergency services immediately.',
            'link'=>'emergency_sos.php',
            'link_text'=>'Emergency SOS'
        ],
        [
            'keywords'=>['what can you do','help me','features'],
            'message'=>'I can help you find CarePlus features including appointments, doctors, symptom checking, health records, medicine reminders, health monitoring, risk prediction, video consultation, QR Medical Card and Emergency SOS.'
        ]
    ];

    foreach ($responses as $response) {
        foreach ($response['keywords'] as $keyword) {
            if (str_contains($message, $keyword)) {
                return [
                    'message'=>$response['message'],
                    'type'=>'normal',
                    'link'=>$response['link'] ?? null,
                    'link_text'=>$response['link_text'] ?? null
                ];
            }
        }
    }

    return [
        'message'=>"I couldn't understand that request. I can help with CarePlus appointments, doctors, symptoms, medical records, medicine reminders, health monitoring, risk prediction, video consultations, QR Medical Cards and Emergency SOS.",
        'type'=>'normal',
        'link'=>null,
        'link_text'=>null
    ];
}