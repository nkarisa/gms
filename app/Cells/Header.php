<?php 

namespace App\Cells;

class Header
{
    public function show(): string
    {
        $primary_user_data_id = 0;
        $session = session();
        $user_can_read_switch = true;
        $user_available_languages = [
            [
                'language_code' => 'eng',
                'language_name' => 'English'
            ]
        ];
        $user_locale = 'eng';
        $default_language = [
            'language_code' => 'eng',
            'language_name' => 'English'
        ];
        $text_align = 'right-to-left';
        $user_icon = '2.png';

        $user = [
            'user_id' => $session->get('user_id'),
            'name' => $session->get('name'),
            'primary_user_data_id' => $primary_user_data_id,
            'user_can_read_switch' => $user_can_read_switch,
            'user_available_languages' => $user_available_languages,
            'user_locale' => $user_locale,
            'default_language' => $default_language,
            'text_align' => $text_align,
            'user_icon' => $user_icon,
        ];

        return view("components/header", $user);
    }
}