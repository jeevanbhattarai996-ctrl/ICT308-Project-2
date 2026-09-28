<?php
/**
 * Rule-based Symptom Checker engine.
 * This is NOT a machine-learning model — it is a transparent, explainable
 * scoring system: each condition has a defining symptom set, and we score
 * how much of that set overlaps with what the patient selected.
 * This is intentionally disclosed to the user (see symptom_checker.php).
 */

function getSymptomList() {
    return [
        'fever'                  => 'Fever',
        'cough'                  => 'Cough',
        'headache'               => 'Headache',
        'body_ache'              => 'Body aches',
        'fatigue'                => 'Fatigue / tiredness',
        'sore_throat'            => 'Sore throat',
        'runny_nose'             => 'Runny or blocked nose',
        'sneezing'               => 'Sneezing',
        'itchy_eyes'             => 'Itchy / watery eyes',
        'rash'                   => 'Skin rash',
        'loss_of_taste_smell'    => 'Loss of taste or smell',
        'shortness_of_breath'    => 'Shortness of breath',
        'wheezing'               => 'Wheezing',
        'chest_tightness'        => 'Chest tightness',
        'chest_pain'             => 'Chest pain',
        'nausea'                 => 'Nausea',
        'vomiting'               => 'Vomiting',
        'diarrhea'               => 'Diarrhea',
        'abdominal_pain'         => 'Abdominal pain',
        'dizziness'              => 'Dizziness',
        'light_sensitivity'      => 'Sensitivity to light',
        'vision_changes'         => 'Vision changes',
        'burning_urination'      => 'Burning sensation when urinating',
        'frequent_urination'     => 'Frequent urination',
        'rapid_heartbeat'        => 'Rapid heartbeat',
        'sweating'               => 'Excessive sweating',
        'difficulty_breathing'   => 'Severe difficulty breathing',
        'severe_bleeding'        => 'Severe / uncontrolled bleeding',
        'loss_of_consciousness'  => 'Fainting or loss of consciousness',
        'slurred_speech'         => 'Slurred speech or facial drooping',
    ];
}

// Symptoms that, on their own, warrant an emergency banner regardless of scoring
function getEmergencySymptoms() {
    return [
        'chest_pain', 'difficulty_breathing', 'severe_bleeding',
        'loss_of_consciousness', 'slurred_speech'
    ];
}

function getConditionRules() {
    return [
        'Flu'                       => ['symptoms' => ['fever','cough','headache','body_ache','fatigue','sore_throat'], 'specialisation' => 'General Practice'],
        'Common Cold'               => ['symptoms' => ['runny_nose','sneezing','sore_throat','cough','fatigue'], 'specialisation' => 'General Practice'],
        'COVID-19'                  => ['symptoms' => ['fever','cough','loss_of_taste_smell','shortness_of_breath','fatigue'], 'specialisation' => 'General Practice'],
        'Seasonal Allergy'          => ['symptoms' => ['sneezing','runny_nose','itchy_eyes','rash'], 'specialisation' => 'General Practice'],
        'Migraine'                  => ['symptoms' => ['headache','nausea','light_sensitivity','vision_changes'], 'specialisation' => 'Neurology'],
        'Gastroenteritis'           => ['symptoms' => ['nausea','vomiting','diarrhea','abdominal_pain','fever'], 'specialisation' => 'Gastroenterology'],
        'Cardiac-related concern'   => ['symptoms' => ['chest_pain','shortness_of_breath','dizziness','sweating','rapid_heartbeat'], 'specialisation' => 'Cardiology'],
        'Urinary Tract Infection'   => ['symptoms' => ['burning_urination','frequent_urination','abdominal_pain','fever'], 'specialisation' => 'General Practice'],
        'Asthma / Respiratory'      => ['symptoms' => ['shortness_of_breath','wheezing','cough','chest_tightness'], 'specialisation' => 'Cardiology'],
        'Anxiety / Panic response'  => ['symptoms' => ['rapid_heartbeat','sweating','dizziness','shortness_of_breath'], 'specialisation' => 'General Practice'],
        'Possible Stroke'           => ['symptoms' => ['slurred_speech','vision_changes','dizziness','loss_of_consciousness'], 'specialisation' => 'General Practice'],
    ];
}

/**
 * @param array $selected list of symptom keys the patient checked
 * @return array ['results' => [[condition, percent, specialisation], ...], 'emergency' => bool, 'recommended' => specialisation]
 */
function runSymptomCheck(array $selected) {
    $rules = getConditionRules();
    $scores = [];

    foreach ($rules as $condition => $data) {
        $condSymptoms = $data['symptoms'];
        $matched = array_intersect($selected, $condSymptoms);
        $matchCount = count($matched);
        if ($matchCount === 0) continue;
        // Score = how much of the condition's symptom profile is explained by patient input
        $rawScore = $matchCount / count($condSymptoms);
        $scores[$condition] = [
            'raw' => $rawScore,
            'matched' => $matchCount,
            'specialisation' => $data['specialisation'],
        ];
    }

    // Sort by raw score desc, take top 3
    uasort($scores, fn($a, $b) => $b['raw'] <=> $a['raw']);
    $top = array_slice($scores, 0, 3, true);

    // Normalise top scores to sum to 100 for a readable "likelihood share"
    $sumRaw = array_sum(array_column($top, 'raw'));
    $results = [];
    foreach ($top as $condition => $data) {
        $percent = $sumRaw > 0 ? round(($data['raw'] / $sumRaw) * 100) : 0;
        $results[] = [
            'condition' => $condition,
            'percent' => $percent,
            'specialisation' => $data['specialisation'],
        ];
    }

    $emergencySymptoms = getEmergencySymptoms();
    $isEmergency = count(array_intersect($selected, $emergencySymptoms)) > 0;

    $recommended = 'General Practice';
    if (!empty($results)) {
        $recommended = $results[0]['specialisation'];
    }

    return [
        'results' => $results,
        'emergency' => $isEmergency,
        'recommended' => $recommended,
    ];
}
