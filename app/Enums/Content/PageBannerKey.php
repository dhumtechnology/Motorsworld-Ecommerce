<?php

namespace App\Enums\Content;

enum PageBannerKey: string
{
    case About = 'about';
    case Services = 'services';
    case ServicesList = 'services_list';
    case ServicesBooking = 'services_booking';
    case Help = 'help';
    case Contact = 'contact';
    case Faq = 'faq';
    case Blog = 'blog';
    case ClaimBook = 'claim_book';
    case PrivacyPolicy = 'privacy_policy';
    case Wholesale = 'wholesale';

    public function label(): string
    {
        return match ($this) {
            self::About => 'Nosotros',
            self::Services => 'Servicios',
            self::ServicesList => 'Lista de servicios',
            self::ServicesBooking => 'Agendar servicio',
            self::Help => 'Centro de ayuda',
            self::Contact => 'Contacto',
            self::Faq => 'Preguntas frecuentes',
            self::Blog => 'Blog',
            self::ClaimBook => 'Libro de reclamaciones',
            self::PrivacyPolicy => 'Política de privacidad',
            self::Wholesale => 'Ventas al por mayor',
        };
    }

    public function shopRouteName(): string
    {
        return match ($this) {
            self::About => 'shop.about',
            self::Services => 'shop.services.index',
            self::ServicesList => 'shop.services.list',
            self::ServicesBooking => 'shop.services.booking',
            self::Help => 'shop.help',
            self::Contact => 'shop.contact',
            self::Faq => 'shop.faq',
            self::Blog => 'shop.blog.index',
            self::ClaimBook => 'shop.claim-book',
            self::PrivacyPolicy => 'shop.privacy-policy',
            self::Wholesale => 'shop.wholesale',
        };
    }

    public function defaultPublicPath(): string
    {
        return match ($this) {
            self::About => 'images/about/NOSOTROS.jpg',
            self::Services,
            self::ServicesList,
            self::ServicesBooking,
            self::Help => 'images/home/portadas/NUESTROS SERVICIOS.jpg',
            self::Contact,
            self::Faq,
            self::PrivacyPolicy => 'images/home/portadas/Enllantado.jpg',
            self::Blog => 'images/home/portadas/BLOG.jpg',
            self::ClaimBook => 'images/home/portadas/Scaneo de motocicleta.jpg',
            self::Wholesale => 'images/home/portadas/REPUESTOS GENERALES .jpeg',
        };
    }
}
