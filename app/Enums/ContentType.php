<?php

namespace App\Enums;

enum ContentType: string
{
    case Lexeme = 'lexeme';
    case Phrase = 'phrase';
    case ExampleSentence = 'example_sentence';
    case GrammarTopic = 'grammar_topic';
    case Exercise = 'exercise';
    case Region = 'region';
    case Location = 'location';
    case Npc = 'npc';
    case ItemDefinition = 'item_definition';
    case Mission = 'mission';
    case ConversationScenario = 'conversation_scenario';

    public function label(): string
    {
        return match ($this) {
            self::Lexeme => 'Woord',
            self::Phrase => 'Woordgroep',
            self::ExampleSentence => 'Voorbeeldzin',
            self::GrammarTopic => 'Grammaticaonderwerp',
            self::Exercise => 'Oefening',
            self::Region => 'Regio',
            self::Location => 'Locatie',
            self::Npc => 'Personage',
            self::ItemDefinition => 'Voorwerp',
            self::Mission => 'Missie',
            self::ConversationScenario => 'Gespreksscenario',
        };
    }
}
