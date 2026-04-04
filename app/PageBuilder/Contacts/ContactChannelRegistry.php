<?php

namespace App\PageBuilder\Contacts;

/**
 * Single source of truth: labels, icons, internal/external, default new-tab policy, helper text.
 */
final class ContactChannelRegistry
{
    /**
     * @return array<string, string> value => label for Filament select
     */
    public function selectOptionsGrouped(): array
    {
        $primary = [
            ContactChannelType::Phone->value => 'Телефон',
            ContactChannelType::Email->value => 'Email',
            ContactChannelType::Telegram->value => 'Telegram',
            ContactChannelType::Vk->value => 'ВКонтакте',
            ContactChannelType::SiteForm->value => 'Форма на сайте',
        ];
        $secondary = [
            ContactChannelType::Whatsapp->value => 'WhatsApp',
            ContactChannelType::Viber->value => 'Viber',
            ContactChannelType::Instagram->value => 'Instagram',
            ContactChannelType::FacebookMessenger->value => 'Facebook Messenger',
            ContactChannelType::Sms->value => 'SMS',
            ContactChannelType::Max->value => 'MAX',
            ContactChannelType::GenericUrl->value => 'Ссылка / другой канал',
        ];

        return $primary + $secondary;
    }

    public function label(ContactChannelType $type): string
    {
        return $this->selectOptionsGrouped()[$type->value] ?? $type->value;
    }

    public function isInternal(ContactChannelType $type): bool
    {
        return match ($type) {
            ContactChannelType::SiteForm => true,
            ContactChannelType::Phone, ContactChannelType::Email, ContactChannelType::Sms => true,
            default => false,
        };
    }

    public function defaultOpenInNewTab(ContactChannelType $type): bool
    {
        return ! $this->isInternal($type);
    }

    public function defaultCtaLabel(ContactChannelType $type): string
    {
        return match ($type) {
            ContactChannelType::Phone => 'Позвонить',
            ContactChannelType::Email => 'Написать на почту',
            ContactChannelType::Telegram => 'Написать в Telegram',
            ContactChannelType::Vk => 'Написать ВКонтакте',
            ContactChannelType::SiteForm => 'Оставить заявку',
            ContactChannelType::Whatsapp => 'Написать в WhatsApp',
            ContactChannelType::Viber => 'Написать в Viber',
            ContactChannelType::Instagram => 'Открыть Instagram',
            ContactChannelType::FacebookMessenger => 'Написать в Messenger',
            ContactChannelType::Sms => 'Отправить SMS',
            ContactChannelType::Max => 'Написать в MAX',
            ContactChannelType::GenericUrl => 'Перейти',
        };
    }

    public function icon(ContactChannelType $type): string
    {
        return match ($type) {
            ContactChannelType::Phone => 'heroicon-o-phone',
            ContactChannelType::Email => 'heroicon-o-envelope',
            ContactChannelType::Telegram => 'heroicon-o-paper-airplane',
            ContactChannelType::Vk => 'heroicon-o-chat-bubble-left-right',
            ContactChannelType::SiteForm => 'heroicon-o-clipboard-document-check',
            ContactChannelType::Whatsapp => 'heroicon-o-chat-bubble-left-ellipsis',
            ContactChannelType::Viber => 'heroicon-o-chat-bubble-oval-left',
            ContactChannelType::Instagram => 'heroicon-o-camera',
            ContactChannelType::FacebookMessenger => 'heroicon-o-chat-bubble-oval-left-ellipsis',
            ContactChannelType::Sms => 'heroicon-o-device-phone-mobile',
            ContactChannelType::Max => 'heroicon-o-link',
            ContactChannelType::GenericUrl => 'heroicon-o-link',
        };
    }

    public function valueHelperText(ContactChannelType $type): string
    {
        return match ($type) {
            ContactChannelType::Phone => 'Номер в любом удобном формате — для набора используется tel:.',
            ContactChannelType::Email => 'Адрес почты для mailto:.',
            ContactChannelType::Telegram => 'Имя без @, полная ссылка https://t.me/… или без схемы t.me/… / telegram.me/…',
            ContactChannelType::Vk => 'Короткое имя (durov), полный URL или без схемы vk.com/…',
            ContactChannelType::SiteForm => 'Якорь (#lead-form), путь (/contacts#form) или относительный URL на эту же витрину. Убедитесь, что блок формы есть на странице.',
            ContactChannelType::Whatsapp => 'Номер (цифры для wa.me) или ссылка https://wa.me/… / без схемы wa.me/…',
            ContactChannelType::Viber => 'Номер, viber://… или https://… (длинные ссылки допускаются).',
            ContactChannelType::Instagram => 'Логин, полный URL или без схемы instagram.com/…',
            ContactChannelType::FacebookMessenger => 'Имя/ID страницы, https://m.me/… или без схемы m.me/…',
            ContactChannelType::Sms => 'Номер для sms:',
            ContactChannelType::Max => 'Полная ссылка https://max.ru/…, или без схемы max.ru/…, ник (как в профиле), путь u/… или @бот.',
            ContactChannelType::GenericUrl => 'Ссылка https://… (в т.ч. с параметрами и нестандартными доменами). Подпись ниже обязательна.',
        };
    }

    /**
     * @return list<ContactChannelType>
     */
    public function allTypes(): array
    {
        return ContactChannelType::cases();
    }
}
