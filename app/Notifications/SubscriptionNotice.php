<?php

namespace App\Notifications;

use App\Models\Store;
use App\Support\Format;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * E-mails da assinatura para o lojista.
 * Enviado na hora (sem fila), porque a hospedagem compartilhada não roda worker.
 */
class SubscriptionNotice extends Notification
{
    public const TRIAL_ENDING = 'trial_ending';

    public const TRIAL_LAST_DAY = 'trial_last_day';

    public const OVERDUE = 'overdue';

    public const OFFLINE = 'offline';

    public const PAID = 'paid';

    public const CANCELED = 'canceled';

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public Store $store, public string $type, public array $data = []) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $store = $this->store;
        $url = url('painel/'.$store->slug.'/assinatura');
        $date = fn (?Carbon $d) => $d?->format('d/m/Y') ?? '—';
        $trialEnds = $date($store->trial_ends_at);
        $blocksAt = $date($this->data['blocks_at'] ?? null);
        $paidUntil = $date($this->data['paid_until'] ?? null);
        $until = $this->data['until'] ?? null;

        $mail = new MailMessage;

        return match ($this->type) {
            self::TRIAL_ENDING => $mail
                ->subject("Seu teste grátis termina em {$this->data['days']} dias — {$store->name}")
                ->greeting('Seu teste está acabando')
                ->line("O período de teste da **{$store->name}** termina em **{$trialEnds}**.")
                ->line('Assine para manter sua vitrine no ar sem interrupção. Se assinar agora, a primeira cobrança só vence no fim do teste: você não perde nenhum dia grátis.')
                ->action('Assinar agora', $url)
                ->line('Pagamento por Pix, boleto ou cartão. Sem fidelidade.'),

            self::TRIAL_LAST_DAY => $mail
                ->subject("Último dia do seu teste grátis — {$store->name}")
                ->greeting('Hoje é o último dia do teste')
                ->line("O teste da **{$store->name}** termina em **{$trialEnds}**. Depois disso, a vitrine sai do ar até a assinatura ser feita.")
                ->action('Assinar e manter a vitrine no ar', $url)
                ->line('Seus produtos e pedidos continuam salvos.'),

            self::OVERDUE => $mail
                ->error()
                ->subject("Pagamento em atraso — {$store->name}")
                ->greeting('Não identificamos o pagamento')
                ->line("A mensalidade da **{$store->name}** está em aberto.")
                ->line("Se o pagamento não for feito, **sua vitrine sai do ar em {$blocksAt}**.")
                ->action('Ver cobrança e pagar', $url)
                ->line('Se você já pagou, desconsidere: a confirmação pode levar até 1 dia útil no boleto.'),

            self::OFFLINE => $mail
                ->error()
                ->subject("Sua vitrine está fora do ar — {$store->name}")
                ->greeting('Sua vitrine saiu do ar')
                ->line(($this->data['reason'] ?? null) === 'trial'
                    ? "O período de teste da **{$store->name}** terminou e a assinatura não foi feita."
                    : "Não recebemos o pagamento da mensalidade da **{$store->name}** dentro do prazo.")
                ->line('Seus clientes veem um aviso de catálogo indisponível. Seus produtos, fotos e pedidos continuam salvos.')
                ->action('Reativar minha vitrine', $url)
                ->line('Assim que o pagamento for confirmado, a vitrine volta automaticamente.'),

            self::PAID => $mail
                ->success()
                ->subject(($this->data['reactivated'] ?? false)
                    ? "Sua vitrine está no ar de novo — {$store->name}"
                    : "Pagamento confirmado — {$store->name}")
                ->greeting(($this->data['reactivated'] ?? false) ? 'Vitrine reativada!' : 'Pagamento confirmado')
                ->line('Recebemos o pagamento de **'.Format::money($this->data['value'] ?? 0)."** da assinatura da **{$store->name}**.")
                ->line("Sua assinatura está paga até **{$paidUntil}**.")
                ->action('Abrir o painel', url('painel/'.$store->slug)),

            self::CANCELED => $mail
                ->subject("Assinatura cancelada — {$store->name}")
                ->greeting('Assinatura cancelada')
                ->line("A assinatura da **{$store->name}** foi cancelada e não haverá novas cobranças.")
                ->line($until && $until->isFuture()
                    ? 'Sua vitrine continua no ar até **'.$date($until).'**.'
                    : 'Sua vitrine sai do ar a partir de agora.')
                ->line('Seus produtos e pedidos continuam salvos. Você pode voltar quando quiser, pela página Assinatura do painel.')
                ->action('Ver minha assinatura', $url),
        };
    }
}
