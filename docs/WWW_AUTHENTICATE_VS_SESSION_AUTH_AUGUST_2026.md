# `WWW-Authenticate` vs. Session Auth — Plain-English Explainer

## Background

Written while investigating the [Authentication DI crash fix](AUTHENTICATION_DI_CRASH_FIX_AUGUST_2026.md)
and reading through `yiisoft/auth`'s active interface-segregation work
([issue #113](https://github.com/yiisoft/auth/issues/113),
[PR #115](https://github.com/yiisoft/auth/pull/115),
[PR #125](https://github.com/yiisoft/auth/pull/125)). The exception this
app actually hit —

```
No definition or class found for "Yiisoft\Auth\Middleware\Authentication" ID.
— Yiisoft\Auth\Di\NotFoundException (Code #0)

No definition or class found or resolvable for "Yiisoft\Auth\AuthenticatorInterface"
while building "Yiisoft\Auth\Middleware\Authentication" -> "Yiisoft\Auth\AuthenticatorInterface".

Ensure that either a service with ID "Yiisoft\Auth\Middleware\Authentication" is defined
or such class exists and is autoloadable.
```

— is technically correct but actively misleading: its own remediation
sentence says to check that the `Authentication` class "exists and is
autoloadable," which was never the problem. The real cause is one
dependency deeper (`AuthenticatorInterface` has no bound implementation),
and the message never says so, nor offers an example fix.

## Proposed replacement message

```
Cannot construct Yiisoft\Auth\Middleware\Authentication: no implementation is bound
for Yiisoft\Auth\AuthenticatorInterface.

This middleware needs a concrete authenticator (e.g. HttpBasic, HttpBearer, Composite)
wired to Yiisoft\Auth\AuthenticatorInterface in your DI configuration before it can be
used — e.g. in config/web/di/auth.php:

    AuthenticatorInterface::class => HttpBearer::class,

If you don't need HTTP-challenge-style authentication (session/cookie login instead),
don't apply this middleware at all.
```

This names the actual missing binding by its real interface name, gives a
copy-pasteable one-line fix, and — the part the current message can't
offer because it doesn't know the caller's intent — names the equally
valid alternative: don't apply the middleware at all, which is what this
app's own fix ultimately was.

## The underlying confusion, explained plainly

The deeper reason this trips people up isn't the wording of one exception
— it's that `yiisoft/auth`'s "Authentication" and `yiisoft/user`'s
session-based login solve two genuinely different problems that share a
name. Once the difference clicks, the exception (and the fix) is obvious.

### What

**Session-based login** is like a **festival wristband**. You show up
once, someone checks your ID at the gate and puts a wristband on you
(that's "logging in" — the wristband is a cookie). After that, every time
you walk up to any stage, the guard just glances at your wrist — no ID,
no questions. If you're not wearing one, the guard doesn't interrogate you
on the spot; he points you back to the check-in booth (a redirect to a
login *page*).

**HTTP-challenge-style authentication** (`WWW-Authenticate`, Basic/Bearer)
is like a **bouncer with no wristbands at all**. Every single door, every
single time, you show ID right there. There's no check-in booth to walk
you to — the bouncer just states the exact rule on the spot: *"I only
accept passport or driver's licence."* That sentence — the exact list of
ID formats he'll accept — is literally what the `WWW-Authenticate` header
is. You then hand over that exact ID on your very next attempt.

### Why

Session-based login exists because a **human sitting in a browser,
clicking around**, doesn't want to retype a password on every click. A
wristband remembers them for a while. When they're not wearing one, the
sensible fix is a human-friendly action: walk them to a desk with a form
on it.

HTTP-challenge-style authentication exists because the "visitor" isn't a
human with hands to fill in a form — it's a **program**: a script, another
company's server, a phone app talking straight to an API. A program can't
walk itself to a login page and click a mouse. It just needs a flat,
machine-readable instruction — *"attach a Bearer token"* — so it can retry
the exact same request correctly, with no human involved anywhere.

### When

Ask one question: **is a human clicking through pages in a browser, or is
a program making one standalone request?**

- **Human in a browser, multi-page session → session-based.** That's
  every page in this app today, correctly.
- **Program, one-shot request, no browser involved → HTTP-challenge-style.**
  This app has exactly one endpoint shaped like that right now: the
  recurring-invoice cron trigger (`InvRecurringController::cron()`) — a
  script (`curl` from a cron scheduler) hitting a URL once, no human, no
  browser. It currently does the wrong thing for its own shape: it checks
  a secret glued into the URL query string instead of the
  ID-on-request pattern challenge-style auth was built for.

The trigger for this app ever needing `WWW-Authenticate` for real isn't
"`yiisoft/auth` is installed" — it's the day this app grows **any**
feature where a program, not a person, talks to it directly and expects a
standard machine-readable retry instruction back. Until that day, the
mechanism has nothing to do here, which is exactly why removing
`Authentication::class` from `RoutePermission::invoiceGroup()` was the
correct fix rather than trying to configure it.

## Translations

AI-assisted translations, not reviewed by a native speaker — corrections
welcome via PR. Chosen deliberately to balance `yiisoft/auth`'s own
maintainer base: Persian for Yii's historically large Iranian developer
community, alongside Russian (which samdark and vjik, this doc's intended
upstream audience, already read natively); Portuguese for Brazil, whose
mandatory e-invoicing (NFe) regime is years ahead of the UK's own
still-pending 2029 mandate — the exact problem domain this app exists for.

<details>
<summary>🇮🇷 فارسی (Persian)</summary>

<div dir="rtl">

### پیش‌زمینه

این متن هنگام بررسی رفع خرابی تزریق وابستگی (DI) در Authentication و
مطالعه‌ی کار فعال جداسازی رابط‌ها (interface segregation) در
`yiisoft/auth` (ایشو #۱۱۳، PR #۱۱۵، PR #۱۲۵) نوشته شده است. خطایی که این
برنامه واقعاً با آن مواجه شد از نظر فنی درست است، اما گمراه‌کننده است:
جمله‌ی راهکار پیشنهادی خودِ آن می‌گوید بررسی کنید که کلاس `Authentication`
«وجود دارد و به‌طور خودکار بارگذاری می‌شود» (autoloadable)، در حالی که این
هرگز مشکل نبوده است. علت واقعی یک وابستگی عمیق‌تر است (رابط
`AuthenticatorInterface` هیچ پیاده‌سازی متصلی ندارد)، و پیام هرگز این را
نمی‌گوید، و هیچ نمونه‌ای برای رفع آن ارائه نمی‌دهد.

### پیام پیشنهادی جایگزین

این پیام، اتصال واقعاً گمشده را با نام واقعی رابط آن مشخص می‌کند، یک
راه‌حل یک‌خطی آماده برای کپی ارائه می‌دهد، و — بخشی که پیام فعلی نمی‌تواند
ارائه دهد چون از نیت فراخواننده اطلاعی ندارد — جایگزین به همان اندازه
معتبر را نام می‌برد: اصلاً میان‌افزار (middleware) را اعمال نکنید، که در
نهایت همان راه‌حلی بود که در این برنامه به کار رفت.

### سردرگمی زیربنایی، به‌زبان ساده

دلیل عمیق‌تر این سردرگمی، متن یک پیام خطا نیست — بلکه این است که
«Authentication» در `yiisoft/auth` و ورود مبتنی بر نشست (session) در
`yiisoft/user`، دو مسئله‌ی کاملاً متفاوت را حل می‌کنند که تصادفاً نامی
مشترک دارند. به‌محض این‌که این تفاوت روشن شود، خطا (و راه‌حل آن) کاملاً
بدیهی می‌شود.

**چیستی**

ورود مبتنی بر نشست مانند دستبند یک جشنواره است. شما یک‌بار می‌آیید، کسی
در دروازه هویت شما را بررسی می‌کند و یک دستبند به شما می‌بندد (این همان
«ورود به سیستم» است — دستبند همان کوکی است). پس از آن، هر بار که به سمت
هر سِن می‌روید، نگهبان فقط نگاهی به مچ دست شما می‌اندازد — بدون مدرک،
بدون سؤال. اگر دستبند نداشته باشید، نگهبان همان‌جا از شما بازجویی
نمی‌کند؛ او شما را به غرفه‌ی ثبت‌نام برمی‌گرداند (یعنی هدایت مجدد به یک
صفحه‌ی ورود).

احراز هویت به سبک چالش HTTP (سرآیند `WWW-Authenticate`، روش‌های
Basic/Bearer) مانند نگهبانی است که اصلاً دستبند نمی‌دهد. در هر دری، هر
بار، باید همان‌جا مدرک نشان دهید. هیچ غرفه‌ی ثبت‌نامی برای فرستادن شما
وجود ندارد — نگهبان فقط همان‌جا قانون دقیق را اعلام می‌کند: «من فقط
پاسپورت یا گواهی‌نامه‌ی رانندگی قبول می‌کنم.» همان جمله — یعنی فهرست دقیق
فرمت‌های مدرکی که او قبول می‌کند — دقیقاً همان چیزی است که سرآیند
`WWW-Authenticate` است. سپس شما همان مدرک دقیق را در تلاش بعدی خود ارائه
می‌دهید.

**چرایی**

ورود مبتنی بر نشست به این دلیل وجود دارد که یک انسان که پشت مرورگر
نشسته و کلیک می‌کند، نمی‌خواهد در هر کلیک دوباره رمز عبور را تایپ کند.
دستبند برای مدتی او را به یاد می‌آورد. وقتی دستبند ندارد، راه‌حل منطقی
یک اقدام انسان‌دوستانه است: او را به میزی با یک فرم هدایت کنید.

احراز هویت به سبک چالش HTTP وجود دارد چون «بازدیدکننده» یک انسان با دست
برای پرکردن فرم نیست — بلکه یک برنامه است: یک اسکریپت، سرور شرکتی دیگر،
یا برنامه‌ی موبایلی که مستقیماً با یک API صحبت می‌کند. یک برنامه
نمی‌تواند خودش را به صفحه‌ی ورود ببرد و با ماوس کلیک کند. آن فقط به یک
دستور ساده و قابل‌خواندن توسط ماشین نیاز دارد — «یک توکن Bearer پیوست
کن» — تا بتواند دقیقاً همان درخواست را به‌درستی دوباره امتحان کند،
بدون این‌که هیچ انسانی در هیچ‌کجای این فرایند دخیل باشد.

**چه زمانی**

یک سؤال بپرسید: آیا یک انسان در مرورگر بین صفحات کلیک می‌کند، یا یک
برنامه یک درخواست مستقل و یک‌باره ارسال می‌کند؟

- انسان در مرورگر، نشستِ چندصفحه‌ای ← مبتنی بر نشست. این دقیقاً همان
  چیزی است که امروز در تمام صفحات این برنامه، به‌درستی، اتفاق می‌افتد.
- برنامه، یک درخواست یک‌باره، بدون مرورگر ← سبک چالش HTTP. این برنامه در
  حال حاضر دقیقاً یک نقطه‌پایانی (endpoint) با این شکل دارد: محرک
  زمان‌بندی‌شده‌ی فاکتورهای تکرارشونده (`InvRecurringController::cron()`)
  — یک اسکریپت (`curl` از یک زمان‌بند cron) که یک‌بار یک URL را فراخوانی
  می‌کند، بدون انسان، بدون مرورگر. این بخش در حال حاضر برای شکل خودش کار
  اشتباهی انجام می‌دهد: به‌جای الگوی «مدرک به‌همراه درخواست» که احراز
  هویت به سبک چالش برای آن ساخته شده، یک رمز مخفی که در رشته‌ی پرسمان URL
  چسبانده شده را بررسی می‌کند.

محرکِ نیاز واقعی این برنامه به `WWW-Authenticate` این نیست که
«`yiisoft/auth` نصب شده است» — بلکه روزی است که این برنامه ویژگی‌ای پیدا
کند که در آن یک برنامه، نه یک انسان، مستقیماً با آن صحبت می‌کند و انتظار
یک دستور استاندارد و قابل‌خواندن توسط ماشین برای تلاش مجدد دارد. تا آن
روز، این مکانیزم اینجا کاری برای انجام‌دادن ندارد، و دقیقاً به همین دلیل
حذف `Authentication::class` از `RoutePermission::invoiceGroup()` راه‌حل
درست بود، نه تلاش برای پیکربندی آن.

</div>

</details>

<details>
<summary>🇵🇹 Português (Portuguese)</summary>

### Contexto

Escrito durante a investigação da correção do crash de DI do
Authentication e a leitura do trabalho ativo de segregação de interfaces
do `yiisoft/auth` (issue #113, PR #115, PR #125). A exceção que esta
aplicação realmente encontrou é tecnicamente correta, mas ativamente
enganosa: sua própria frase de correção diz para verificar se a classe
`Authentication` "existe e pode ser carregada automaticamente"
(autoloadable), o que nunca foi o problema. A causa real está uma
dependência mais abaixo (a interface `AuthenticatorInterface` não tem
implementação vinculada), e a mensagem nunca diz isso, nem oferece um
exemplo de correção.

### Mensagem de substituição proposta

Isso nomeia a vinculação realmente ausente pelo seu nome real de
interface, oferece uma correção de uma linha pronta para copiar e colar
e — a parte que a mensagem atual não pode oferecer porque não conhece a
intenção de quem a chamou — indica a alternativa igualmente válida:
simplesmente não aplicar o middleware, que foi exatamente a correção
final desta aplicação.

### A confusão de fundo, explicada de forma simples

O motivo mais profundo pelo qual isso confunde as pessoas não é a
redação de uma única exceção — é que o "Authentication" do
`yiisoft/auth` e o login baseado em sessão do `yiisoft/user` resolvem
dois problemas genuinamente diferentes que compartilham o mesmo nome.
Assim que a diferença fica clara, a exceção (e a correção) se tornam
óbvias.

**O quê**

O login baseado em sessão é como uma pulseira de festival. Você chega
uma vez, alguém verifica sua identidade no portão e coloca uma pulseira
em você (isso é "fazer login" — a pulseira é um cookie). Depois disso,
toda vez que você se aproxima de qualquer palco, o segurança apenas olha
para o seu pulso — sem identidade, sem perguntas. Se você não estiver
usando uma, o segurança não te interroga ali mesmo; ele te encaminha de
volta ao balcão de check-in (um redirecionamento para uma página de
login).

A autenticação no estilo de desafio HTTP (`WWW-Authenticate`,
Basic/Bearer) é como um segurança sem pulseiras. Em cada porta, todas as
vezes, você mostra sua identidade ali mesmo. Não há balcão de check-in
para onde te encaminhar — o segurança apenas declara a regra exata na
hora: "Eu só aceito passaporte ou carteira de motorista." Essa frase — a
lista exata de formatos de identidade que ele aceitará — é literalmente
o que é o cabeçalho `WWW-Authenticate`. Você então entrega essa
identidade exata na sua próxima tentativa.

**Por quê**

O login baseado em sessão existe porque um humano sentado em um
navegador, clicando por aí, não quer digitar a senha novamente a cada
clique. Uma pulseira o lembra por um tempo. Quando ele não está usando
uma, a solução sensata é uma ação amigável para humanos: encaminhá-lo a
um balcão com um formulário.

A autenticação no estilo de desafio HTTP existe porque o "visitante" não
é um humano com mãos para preencher um formulário — é um programa: um
script, o servidor de outra empresa, um aplicativo de celular falando
diretamente com uma API. Um programa não pode se encaminhar sozinho a
uma página de login e clicar com o mouse. Ele só precisa de uma
instrução simples e legível por máquina — "anexe um token Bearer" — para
poder repetir exatamente a mesma requisição corretamente, sem nenhum
humano envolvido em lugar nenhum.

**Quando**

Faça uma pergunta: é um humano clicando em páginas em um navegador, ou é
um programa fazendo uma única requisição independente?

- Humano em um navegador, sessão de múltiplas páginas → baseado em
  sessão. É o que acontece em cada página desta aplicação hoje,
  corretamente.
- Programa, requisição única, sem navegador envolvido → estilo de
  desafio HTTP. Esta aplicação tem exatamente um endpoint com essa forma
  agora: o gatilho de cron de faturas recorrentes
  (`InvRecurringController::cron()`) — um script (`curl` a partir de um
  agendador cron) acessando uma URL uma única vez, sem humano, sem
  navegador. Atualmente ele faz a coisa errada para a sua própria forma:
  verifica um segredo colado na query string da URL, em vez do padrão de
  identidade-na-requisição para o qual a autenticação no estilo de
  desafio foi criada.

O gatilho para esta aplicação realmente precisar do `WWW-Authenticate`
não é "o `yiisoft/auth` está instalado" — é o dia em que esta aplicação
ganhar qualquer funcionalidade em que um programa, não uma pessoa, fale
diretamente com ela e espere de volta uma instrução padrão e legível por
máquina para repetir a tentativa. Até esse dia, o mecanismo não tem nada
a fazer aqui, e é exatamente por isso que remover `Authentication::class`
de `RoutePermission::invoiceGroup()` foi a correção correta, em vez de
tentar configurá-lo.

</details>
