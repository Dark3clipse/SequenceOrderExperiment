<?php
if ($GLOBALS['S_EXPERIMENT_VERSION'] == 0){
    $options = [
        'limit_per_group' => $limit,
        'seed' => $track_id,
        'tags_general' => [
            '0' => [
                'tag' => 'popularity',
                'max' => '80',
            ],
        ],
        'groups' => [
            '0' => [
                'tempo_group' => 'high',
                'mood_group' => 'low_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '0',
                        'max' => '0.18',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '0',
                        'max' => '.37',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '145',
                        'max' => '210',
                    ],
                ],
            ],
            '1' => [
                'tempo_group' => 'low',
                'mood_group' => 'low_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '0',
                        'max' => '0.18',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '0',
                        'max' => '.37',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '0',
                        'max' => '92',
                    ],
                ],
            ],
            '2' => [
                'tempo_group' => 'high',
                'mood_group' => 'high_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '.61',
                        'max' => '1',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '.81',
                        'max' => '1',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '145',
                        'max' => '210',
                    ],
                ],
            ],
            '3' => [
                'tempo_group' => 'low',
                'mood_group' => 'high_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '.61',
                        'max' => '1',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '.81',
                        'max' => '1',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '0',
                        'max' => '92',
                    ],
                ],
            ],
        ],
    ];
    
//new version
}else{
    $options = [
        'limit_per_group' => $limit,
        'seed' => $track_id,
        'tags_general' => [
            '0' => [
                'tag' => 'popularity',
                'max' => '80',
            ],
        ],
        'groups' => [
            '0' => [
                'tempo_group' => 'high',
                'mood_group' => 'low_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '.2',
                        'max' => '.4',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '.2',
                        'max' => '.4',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '130',
                        'max' => '160',
                    ],
                ],
            ],
            '1' => [
                'tempo_group' => 'low',
                'mood_group' => 'low_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '.2',
                        'max' => '.4',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '.2',
                        'max' => '.4',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '80',
                        'max' => '110',
                    ],
                ],
            ],
            '2' => [
                'tempo_group' => 'high',
                'mood_group' => 'high_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '.6',
                        'max' => '.8',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '.6',
                        'max' => '.8',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '130',
                        'max' => '160',
                    ],
                ],
            ],
            '3' => [
                'tempo_group' => 'low',
                'mood_group' => 'high_valence',
                'tags' => [
                    '0' =>[
                        'tag' => 'valence',
                        'min' => '.6',
                        'max' => '.8',
                    ],
                    '1' =>[
                        'tag' => 'energy',
                        'min' => '.6',
                        'max' => '.8',
                    ],
                    '2' =>[
                        'tag' => 'tempo',
                        'min' => '80',
                        'max' => '110',
                    ],
                ],
            ],
        ],
    ];
}
?>