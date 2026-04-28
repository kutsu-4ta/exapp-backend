<?php

namespace App\Enums;

enum Material: string
{
    case Textbook = 'テキスト';
    case Workbook = '問題集';
    case SpeedBook = 'スピード問題集';
    case PastExam = '過去問';
    case Other = 'その他';
}
