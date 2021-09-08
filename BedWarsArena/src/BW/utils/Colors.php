<?php
namespace BW\utils;

use pocketmine\color\Color;

class Colors{

    /**
     * @param string $tag
     * @return int
     */
    static function convertTagToInt(string $tag): int
    {

        $tag = strtolower($tag);

        if ($tag == "red") return 1;
        if ($tag == "yellow") return 2;
        if ($tag == "green") return 3;
        if ($tag == "blue") return 4;
        if ($tag == "white") return 5;
        if ($tag == "black") return 6;
        if ($tag == "purple") return 7;
        if ($tag == "orange") return 8;
        if ($tag == "lime") return 9;
        if ($tag == "cyan") return 10;
        if ($tag == "light_blue") return 11;
        if ($tag == "magenta") return 12;
        if ($tag == "gray") return 13;
        if ($tag == "light_gray") return 14;
        if ($tag == "brown") return 15;
        if ($tag == "pink") return 16;

        return 0;

    }


    /**
     * @param int $team
     * @return string
     */
    static function convertIntToTag(int $team): string
    {

        if ($team == 1) return "red";
        if ($team == 2) return "yellow";
        if ($team == 3) return "green";
        if ($team == 4) return "blue";
        if ($team == 5) return "white";
        if ($team == 6) return "black";
        if ($team == 7) return "purple";
        if ($team == 8) return "orange";
        if ($team == 9) return "lime";
        if ($team == 10) return "cyan";
        if ($team == 11) return "light_blue";
        if ($team == 12) return "magenta";
        if ($team == 13) return "gray";
        if ($team == 14) return "light_gray";
        if ($team == 15) return "brown";
        if ($team == 16) return "pink";

        return "undefined";

    }


    /**
     * @param string $tag
     * @return int
     */
    static function convertToBed(string $tag): int
    {

        if ($tag == "red") return 14;
        if ($tag == "yellow") return 4;
        if ($tag == "green") return 13;
        if ($tag == "blue") return 11;
        if ($tag == "white") return 0;
        if ($tag == "black") return 15;
        if ($tag == "purple") return 10;
        if ($tag == "orange") return 1;
        if ($tag == "lime") return 5;
        if ($tag == "cyan") return 9;
        if ($tag == "light_blue") return 3;
        if ($tag == "magenta") return 2;
        if ($tag == "gray") return 7;
        if ($tag == "light_gray") return 8;
        if ($tag == "brown") return 12;
        if ($tag == "pink") return 6;

        return -1;

    }


    /**
     * @param int $tag
     * @return string
     */
    static function languageConvert(int $tag): string
    {

        if ($tag == 1) return "Красная";
        if ($tag == 2) return "Желтая";
        if ($tag == 3) return "Зеленая";
        if ($tag == 4) return "Синяя";
        if ($tag == 5) return "Белая";
        if ($tag == 6) return "Черная";
        if ($tag == 7) return "Фиолетовая";
        if ($tag == 8) return "Оранжевая";
        if ($tag == 9) return "Липовая";
        if ($tag == 10) return "Голубая";
        if ($tag == 11) return "Светло-синяя";
        if ($tag == 12) return "Пурпурная";
        if ($tag == 13) return "Серая";
        if ($tag == 14) return "Светло-серая";
        if ($tag == 15) return "Коричневая";
        if ($tag == 16) return "Розовая";

        return "Без команды";

    }


    /**
     * @param string $tag
     * @return string
     */
    static function rusToEng(string $tag): string
    {

        if ($tag == "red") return "Красная";
        if ($tag == "yellow") return "Желтая";
        if ($tag == "green") return "Зеленая";
        if ($tag == "blue") return "Синяя";
        if ($tag == "white") return "Белая";
        if ($tag == "black") return "Черная";
        if ($tag == "purple") return "Фиолетовая";
        if ($tag == "orange") return "Оранжевая";
        if ($tag == "lime") return "Липовая";
        if ($tag == "cyan") return "Голубая";
        if ($tag == "light_blue") return "Светло-синяя";
        if ($tag == "magenta") return "Пурпурная";
        if ($tag == "gray") return "Серая";
        if ($tag == "light_gray") return "Светло-серая";
        if ($tag == "brown") return "Коричневая";
        if ($tag == "pink") return "Розовая";

        return "Без команды";

    }


    /**
     * @param int $tag
     * @return string
     */
    static function getCode(int $tag): string
    {

        if ($tag == 1) return "§c";
        if ($tag == 2) return "§e";
        if ($tag == 3) return "§2";
        if ($tag == 4) return "§9";
        if ($tag == 5) return "§f";
        if ($tag == 6) return "§0";
        if ($tag == 7) return "§5";
        if ($tag == 8) return "§6";
        if ($tag == 9) return "§a";
        if ($tag == 10) return "§3";
        if ($tag == 11) return "§b";
        if ($tag == 12) return "§d";
        if ($tag == 13) return "§8";
        if ($tag == 14) return "§7";
        if ($tag == 15) return "§8";
        if ($tag == 16) return "§d";

        return "§f";

    }


    /**
     * @param int $tag
     * @return Color
     */
    static function getColor(int $tag): Color
    {

        if ($tag == 1) return new Color(176, 46, 38);
        if ($tag == 2) return new Color(254, 216, 61);
        if ($tag == 3) return new Color(94, 124, 22);
        if ($tag == 4) return new Color(60, 68, 170);
        if ($tag == 5) return new Color(240, 240, 240);
        if ($tag == 6) return new Color(29, 29, 33);
        if ($tag == 7) return new Color(137, 50, 184);
        if ($tag == 8) return new Color(249, 128, 29);
        if ($tag == 9) return new Color(128, 199, 31);
        if ($tag == 10) return new Color(22, 156, 156);
        if ($tag == 11) return new Color(58, 179, 218);
        if ($tag == 12) return new Color(199, 78, 189);
        if ($tag == 13) return new Color(71, 79, 82);
        if ($tag == 14) return new Color(157, 157, 151);
        if ($tag == 15) return new Color(131, 84, 50);
        if ($tag == 16) return new Color(243, 139, 170);

        return new Color(0, 0, 0);

    }

}