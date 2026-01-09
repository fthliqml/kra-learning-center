<?php

return [
  'sidebar' => [
    // Home - All users
    [
      'id' => 'home',
      'label' => 'Home',
      'icon' => 'home',
      'href' => '/',
    ],

    // Competency - Admin, Instructor, Certificator Role
    [
      'id' => 'competency',
      'label' => 'Competency',
      'icon' => 'archive-box',
      'href' => '/competency',
      'roles' => ['admin', 'instructor', 'certificator'],
      'submenu' => [
        [
          'label' => 'Competency Book',
          'href' => '/competency/book',
          'roles' => ['admin', 'instructor', 'certificator'],
        ],
        [
          'label' => 'Competency Value',
          'href' => '/competency/value',
          'roles' => ['admin', 'instructor', 'certificator'],
        ],
        [
          'label' => 'Competency Matrix',
          'href' => '/competency/matrix',
          'roles' => ['admin', 'instructor', 'certificator'],
        ],
      ],
    ],

    // Development - All users
    [
      'id' => 'development',
      'label' => 'Development',
      'icon' => 'trophy',
      'href' => '/development',
      'submenu' => [
        [
          'label' => 'Development Plan',
          'href' => '/development/plan',
        ],
        [
          'label' => 'Development Approval',
          'href' => '/development/approval',
          'positions' => ['supervisor', 'section_head', 'department_head', 'division_head'],
        ],
        [
          'label' => 'Development Recap',
          'href' => '/development/recap',
          'roles' => ['admin'],
        ],
      ],
    ],

    // Courses - Employee and Supervisor (Exclude LID)
    [
      'id' => 'courses',
      'label' => 'Courses',
      'icon' => 'book-open',
      'href' => '/courses',
      'positions' => ['employee', 'supervisor', 'department_head', 'division_head'],
      'exclude_roles' => ['admin', 'instructor', 'certificator', 'multimedia'],
    ],

    // Training - Employee and Supervisor
    [
      'id' => 'training-employee',
      'label' => 'Training',
      'icon' => 'academic-cap',
      'href' => '#',
      'positions' => ['employee', 'supervisor', 'department_head', 'division_head'],
      'exclude_roles' => ['admin', 'instructor', 'certificator'],
      'submenu' => [
        [
          'label' => 'Training Schedule',
          'href' => '/training/schedule',
          'positions' => ['employee', 'supervisor', 'department_head', 'division_head'],
          'exclude_roles' => ['admin', 'instructor', 'certificator'],
        ],
        [
          'label' => 'Training Test',
          'href' => '/training-test',
          'positions' => ['employee', 'supervisor', 'department_head', 'division_head'],
          'exclude_roles' => ['admin', 'instructor', 'certificator'],
        ],
        [
          'label' => 'Training Approval',
          'href' => '/training/approval',
          'positions' => ['department_head'],
          'exclude_roles' => ['admin', 'instructor', 'certificator'],
        ],
        [
          'label' => 'Training Request',
          'href' => '/training/request',
          'positions' => ['supervisor', 'department_head', 'division_head'],
        ],
      ],
    ],

    // Survey - Employee and Supervisor
    [
      'id' => 'survey',
      'label' => 'Survey',
      'icon' => 'document-text',
      'href' => '#',
      'positions' => ['employee', 'supervisor', 'department_head', 'division_head'],
      'exclude_roles' => ['admin', 'instructor', 'certificator'],
      'submenu' => [
        [
          'label' => 'Survey 1',
          'href' => '/survey/1',
          'positions' => ['employee', 'supervisor', 'department_head', 'division_head'],
          'exclude_roles' => ['admin', 'instructor', 'certificator'],
        ],
        [
          'label' => 'Survey 3',
          'href' => '/survey/3',
          'positions' => ['supervisor', 'department_head', 'division_head'],
        ],
      ],
    ],

    // Training - LID Roles + Leadership Positions
    [
      'id' => 'training-admin',
      'label' => 'Training',
      'icon' => 'academic-cap',
      'href' => '#',
      'positions' => ['section_head'],
      'roles' => ['admin', 'instructor', 'certificator'],
      'submenu' => [
        [
          'label' => 'Trainer',
          'href' => '/training/trainer',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin'],
        ],
        [
          'label' => 'Training Module',
          'href' => '/training/module',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin'],
        ],
        [
          'label' => 'Training Schedule',
          'href' => '/training/schedule',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin'],
        ],
        [
          'label' => 'Training Request',
          'href' => '/training/request',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin'],
        ],
        [
          'label' => 'Training Approval',
          'href' => '/training/approval',
          'positions' => ['section_head'],
        ],
        [
          'label' => 'Test Review',
          'href' => '/test-review',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'admin'],
        ],
      ],
    ],

    // Course Management - LID Roles + Leadership Positions
    [
      'id' => 'courses-management',
      'label' => 'Course',
      'icon' => 'folder-open',
      'href' => '/courses/management',
      'positions' => ['section_head'],
      'roles' => ['multimedia', 'instructor', 'certificator', 'admin'],
    ],

    // Certification - LID Roles + Leadership Positions
    [
      'id' => 'certification',
      'label' => 'Certification',
      'icon' => 'check-badge',
      'href' => '#',
      'positions' => ['section_head', 'department_head', 'division_head'],
      'roles' => ['certificator', 'admin'],
      'submenu' => [
        [
          'label' => 'Certification Module',
          'href' => '/certification/module',
          'positions' => ['section_head'],
          'roles' => ['certificator', 'admin'],
        ],
        [
          'label' => 'Certification Schedule',
          'href' => '/certification/schedule',
          'positions' => ['section_head'],
          'roles' => ['certificator', 'admin'],
        ],
        // [
        //     'label' => 'Certification Point',
        //     'href' => '/certification/point',
        //     'positions' => ['section_head'],
        //     'roles' => ['certificator', 'admin'],
        // ],
        [
          'label' => 'Certification Approval',
          'href' => '/certification/approval',
          'positions' => ['section_head', 'department_head', 'division_head'],
        ],
      ],
    ],

    // Survey Management - LID Roles Only
    [
      'id' => 'survey',
      'label' => 'Survey',
      'icon' => 'document-text',
      'href' => '#',
      'positions' => ['section_head'],
      'roles' => ['instructor', 'certificator', 'admin'],
      'submenu' => [
        [
          'label' => 'Template',
          'href' => '/survey-template',
          'roles' => ['instructor', 'certificator', 'admin'],
        ],
        [
          'label' => 'Survey 1',
          'href' => '/survey/1/management',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin'],
        ],
        [
          'label' => 'Survey 3',
          'href' => '/survey/3/management',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin'],
        ],
      ],
    ],

    // History - All users
    [
      'id' => 'history',
      'label' => 'History',
      'icon' => 'clock',
      'href' => '#',
      'submenu' => [
        [
          'label' => 'Training History',
          'href' => '/training/history',
        ],
        [
          'label' => 'Certification History',
          'href' => '/certification/history',
        ],
      ],
    ],

    // Reports - LID Roles + Leadership Positions
    [
      'id' => 'reports',
      'label' => 'Reports',
      'icon' => 'chart-bar',
      'href' => '#',
      'positions' => ['section_head'],
      'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
      'submenu' => [
        [
          'label' => 'Training Activity',
          'href' => '/reports/training-activity',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
        ],
        [
          'label' => 'Certification Activity',
          'href' => '/reports/certification-activity',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
        ],
        [
          'label' => 'Instructor Daily Record',
          'href' => '/reports/instructor-daily-record',
          'positions' => ['section_head'],
          'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
        ],
      ],
    ],
  ],

  // Sidebar behavior options
  'flatten_child_when_parent_hidden' => true,
];
