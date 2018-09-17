# Плагин ChronoPay
Плагин оплаты [ChronoPay](https://chronopay.com/) для [WebAsyst](https://www.webasyst.com/) и Shop Script.


## Установка
* Клонируйте или скачайте плагин из репозитори в папку `\wa-plugins\payment\chronopay` на вашем сервера
> Если у вас нет таких папок на сервере, то создайте и дайте права 755 (должны стоять по умолчанию)
* Добавьте плагин ChronoPay Payment в настройках: *Настройки* → *Оплата* → *Добавить способ оплаты* → *ChronoPay Payment*

## Настройка плагина

* *Product ID* — Вам должны выдать.
* *SharedSec* — Вам должны выдать.
* *Payments Url* — (по умолчанию https://payments.chronopay.com) это url, где будет генерироваться оплата (если вы понятия не имеете, что это, то оставьте без изменений).
* *Callback Url* — cbUrl (на этот url приходят подтверждения оплаты) для Вашего магазина будет http://yourdomain.com/payments.php/chronopay/. Этот праметр нельзя изменить.
* *Callback URL*, *Callback type*, *Success URL*, *Decline URL*, *Payment types* - устанавливаются вручную, либо в системе ChronoPay.
* *Time limit for payment page in minutes* - максимальное время нахождения пользователя на странице оплаты (в минутах).
* *Expire time for order in minutes* - время истечения резерва заказа (в минутах).