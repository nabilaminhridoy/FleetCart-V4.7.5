<div class="product-details-info position-relative flex-grow-1"> 
    <div class="details-info-top">
        <h1 class="product-name">{{ $product->name }}</h1>

        @if (setting('reviews_enabled'))
                @include('storefront::public.partials.product_rating')
        @endif
        
        <template x-cloak x-if="isInStock">
            <div>
                <template x-if="doesManageStock">
                    <div
                        class="availability in-stock"
                        x-text="trans('storefront::product.left_in_stock', { count: item.qty })"
                    >
                    </div>
                </template>
                
                <template x-if="!doesManageStock">
                    <div class="availability in-stock">
                        {{ trans('storefront::product.in_stock') }}
                    </div>
                </template>
            </div>
        </template>
        
        <template x-if="!isInStock">
            <div class="availability out-of-stock">
                {{ trans('storefront::product.out_of_stock') }}
            </div>
        </template>

        <div class="brief-description" style="text-align: justify;">
            {!! $product->short_description !!}
        </div>

        <div class="details-info-top-actions">
            <button
                class="btn btn-wishlist"
                :class="{ 'added': inWishlist }"
                @click="syncWishlist"
            >
                <template x-if="inWishlist">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M16.44 3.1001C14.63 3.1001 13.01 3.9801 12 5.3301C10.99 3.9801 9.37 3.1001 7.56 3.1001C4.49 3.1001 2 5.6001 2 8.6901C2 9.8801 2.19 10.9801 2.52 12.0001C4.1 17.0001 8.97 19.9901 11.38 20.8101C11.72 20.9301 12.28 20.9301 12.62 20.8101C15.03 19.9901 19.9 17.0001 21.48 12.0001C21.81 10.9801 22 9.8801 22 8.6901C22 5.6001 19.51 3.1001 16.44 3.1001Z" fill="#292D32"/>
                    </svg>
                </template>
                
                <template x-if="!inWishlist">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12.62 20.81C12.28 20.93 11.72 20.93 11.38 20.81C8.48 19.82 2 15.69 2 8.68998C2 5.59998 4.49 3.09998 7.56 3.09998C9.38 3.09998 10.99 3.97998 12 5.33998C13.01 3.97998 14.63 3.09998 16.44 3.09998C19.51 3.09998 22 5.59998 22 8.68998C22 15.69 15.52 19.82 12.62 20.81Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </template>

                {{ trans('storefront::product.wishlist') }}
            </button>

            <button
                class="btn btn-compare"
                :class="{ 'added': inCompareList }"
                @click="syncCompareList"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M3.58008 5.15991H17.4201C19.0801 5.15991 20.4201 6.49991 20.4201 8.15991V11.4799" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M6.74008 2L3.58008 5.15997L6.74008 8.32001" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M20.4201 18.84H6.58008C4.92008 18.84 3.58008 17.5 3.58008 15.84V12.52" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M17.26 21.9999L20.42 18.84L17.26 15.6799" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                
                {{ trans('storefront::product.compare') }}
            </button>
        </div>
    </div>

    <div class="details-info-middle">
        @if ($product->variant)
            <template x-if="isActiveItem">
                <div class="product-price">
                    <template x-if="hasSpecialPrice">
                        <span class="special-price" x-text="formatCurrency(specialPrice)"></span>
                    </template>

                    <span class="previous-price" x-text="formatCurrency(regularPrice)">
                        {!! $item->is_active ? $item->hasSpecialPrice() ? $item->special_price->format() : $item->price->format() : '' !!}
                    </span>
                </div>
            </template>
        @else
            <div class="product-price">
                <template x-if="hasSpecialPrice">
                    <span class="special-price" x-text="formatCurrency(specialPrice)"></span>
                </template>

                <span class="previous-price" x-text="formatCurrency(regularPrice)">
                    {{ $item->hasSpecialPrice() ? $item->special_price->format() : $item->price->format() }}
                </span>
            </div>
        @endif

        <form
            @input="errors.clear($event.target.name)"
            @submit.prevent="addToCart"
        >
            @if ($product->variant)
                <div class="product-variants">
                    @include('storefront::public.products.show.variations')
                </div>
            @endif
            
            @if ($product->options->isNotEmpty())
                <div class="product-variants">
                    @foreach ($product->options as $option)
                        @includeIf("storefront::public.products.show.custom_options.{$option->type}")
                    @endforeach
                </div>
            @endif

            <div class="details-info-middle-actions">
                <div class="number-picker-lg">
                    <label for="qty">{{ trans('storefront::product.quantity') }}</label>

                    <div class="input-group-quantity">
                        <input
                            x-ref="inputQuantity"
                            type="text"
                            :value="cartItemForm.qty"
                            min="1"
                            :max="maxQuantity"
                            id="qty"
                            class="form-control input-number input-quantity"
                            :disabled="isAddToCartDisabled"
                            @focus="$event.target.select()"
                            @input="updateQuantity(Number($event.target.value))"
                            @keydown.up="updateQuantity(cartItemForm.qty + 1)"
                            @keydown.down="updateQuantity(cartItemForm.qty - 1)"
                        >

                        <span class="btn-wrapper">
                            <button
                                type="button"
                                aria-label="quantity"
                                class="btn btn-number btn-plus"
                                :disabled="isQtyIncreaseDisabled"
                                @click="updateQuantity(cartItemForm.qty + 1)"
                            >
                                +
                            </button>

                            <button
                                type="button"
                                aria-label="quantity"
                                class="btn btn-number btn-minus"
                                :disabled="isQtyDecreaseDisabled"
                                @click="updateQuantity(cartItemForm.qty - 1)"
                            >
                                -
                            </button>
                        </span>
                    </div>
                </div>

                <button
                    style="width: 100%;"
                    type="submit"
                    class="btn btn-primary btn-add-to-cart"
                    :class="{'btn-loading': addingToCart }"
                    :disabled="isAddToCartDisabled"
                    x-text="isActiveItem ? '{{ trans('storefront::product.add_to_cart') }}' : '{{ trans('storefront::product.unavailable') }}'"
                >
                    {{ trans($item->is_active ? 'storefront::product.add_to_cart' : 'storefront::product.unavailable') }}
                </button>
            </div>
        </form>
        
        @php
            $currentUrl = urlencode(request()->fullUrl());
        @endphp

        <div style="margin-top: 0.6rem;">
            <a
                href="https://api.whatsapp.com/send?phone=8801531387373&text={{ urlencode('এই প্রোডাক্টের বিষয়ে আরও বিস্তারিত জানতে চাই।' . url()->current()) }}"
                    style="
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: #128c7e;
                    color: white;
                    padding: 0.75rem 1rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    width: 100%;
                    justify-content: center;
                    margin-top: 5px;
                    margin-bottom: 15px;
                    font-family: 'Anek Bangla', sans-serif;
                "
                target="_blank"
                rel="noopener"
            >
                <i class="lab la-whatsapp" style="font-size: 1.1rem;"></i> প্রোডাক্ট স্টক ও অর্ডার করতে হোয়াটসঅ্যাপ করুন 
            </a>

            <a
                href="https://m.me/peculiargadgets?ref={{ $currentUrl }}"
                style="
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: #3b82f6;
                    color: white;
                    padding: 0.75rem 1rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    width: 100%;
                    justify-content: center;
                "
                target="_blank"
                rel="noopener"
            >
                <i class="lab la-facebook-messenger" style="font-size: 1.1rem;"></i> প্রোডাক্ট স্টক ও অর্ডার করতে মেসেজ করুন
            </a>
        </div>

        <div 
            x-data="{
                today: new Date(),
                deliveryDates: [],
                formatDate(date) {
                    return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
                },
                getNextDeliveryDates() {
                    const dates = [];
                    let date = new Date(this.today);

                    while (dates.length < 4) {
                        date.setDate(date.getDate() + 1);
                        if (date.getDay() !== 5) { // 5 = Friday (skip)
                            dates.push(this.formatDate(new Date(date)));
                        }
                    }

                    return dates;
                },
                init() {
                    this.deliveryDates = this.getNextDeliveryDates();
                }
            }"
            x-init="init()"
            style="display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 0.8rem; margin-top: 0.8rem;"
            >
            <svg xmlns="http://www.w3.org/2000/svg" style="width: 25px; height: 25px;" viewBox="0 0 256 256">
                <g transform="translate(1.4066 1.4066) scale(2.81 2.81)">
                <path d="M85.187 28.793H4.813c-1.104 0-2-.896-2-2V16.255c0-5.367 4.366-9.733 9.733-9.733h64.908c5.366 0 9.732 4.366 9.732 9.733v10.539c0 1.104-.896 2-2 2zM6.813 24.793h76.374v-8.539c0-3.161-2.571-5.733-5.732-5.733H12.546c-3.162 0-5.733 2.572-5.733 5.733V24.793z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M43.13 90H12.546c-5.367 0-9.733-4.366-9.733-9.733V26.793c0-1.104.896-2 2-2h80.374c1.104 0 2 .896 2 2v19.15c0 1.104-.896 2-2 2s-2-.896-2-2v-17.15H6.813v51.473c0 3.161 2.572 5.733 5.733 5.733H43.13c1.104 0 2 .896 2 2s-.896 2-2 2z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M20.855 17.045c-1.104 0-2-.896-2-2V2c0-1.104.896-2 2-2s2 .896 2 2v13.045c0 1.104-.896 2-2 2z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M69.145 17.045c-1.104 0-2-.896-2-2V2c0-1.104.896-2 2-2s2 .896 2 2v13.045c0 1.104-.896 2-2 2z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M45 17.045c-1.104 0-2-.896-2-2V2c0-1.104.896-2 2-2s2 .896 2 2v13.045c0 1.104-.896 2-2 2z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M20.814 41.984h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M37.444 41.984h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M54.074 41.984h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M70.705 41.984h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M20.814 58.614h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M37.444 58.614h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M20.814 75.244h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M37.444 75.244h-1.519c-.685 0-1.241-.555-1.241-1.241v-1.519c0-.685.555-1.241 1.241-1.241h1.519c.685 0 1.241.555 1.241 1.241v1.519c0 .686-.556 1.241-1.241 1.241z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M66.77 90c-11.258 0-20.417-9.159-20.417-20.417 0-11.259 9.159-20.418 20.417-20.418s20.417 9.159 20.417 20.418C87.187 80.841 78.027 90 66.77 90zM66.77 53.165c-9.053 0-16.417 7.365-16.417 16.418S57.717 86 66.77 86s16.417-7.364 16.417-16.417S75.822 53.165 66.77 53.165z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M75.503 71.583H66.77c-1.104 0-2-.896-2-2V59.352c0-1.104.896-2 2-2s2 .896 2 2v8.231h6.733c1.104 0 2 .896 2 2s-.896 2-2 2z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                </g>
            </svg>

            <span>
                Inside Dhaka 2–3 Days & Outside Dhaka 3–5 Days</br> 
                <template x-if="deliveryDates.length">
                <span><span x-text="deliveryDates.join(', ')"></span></span>
                </template>
            </span>
        </div>

        <div 
            class="watching-now-message"
            x-data="{ viewers: Math.floor(Math.random() * 21) + 30 }"
            x-init="setInterval(() => viewers = Math.floor(Math.random() * 21) + 30, 20000)"

            style="display: flex; align-items: center; gap: 8px; padding: 12px; border-radius: 8px; background-color: #DBEAFE; color: #1E3A8A; font-size: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); border: 1px solid #93C5FD; margin-top: 0.8rem;"
        >
            <svg xmlns="http://www.w3.org/2000/svg" 
            style="width: 25px; height: 25px; color: #2563EB;" 
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>

            <span x-text="viewers + ' people are watching this right now. Hurry up, it\'s running out!'"></span>
        </div>
    </div>

    <div class="details-info-bottom">
        <ul class="list-inline additional-info">
            <template x-cloak x-if="item.sku">
                <li class="sku">
                    <label>{{ trans('storefront::product.sku') }}</label>
                    
                    <span x-text="item.sku">{{ $item->sku }}</span>
                </li>
            </template>

            @if ($product->categories->isNotEmpty())
                <li>
                    <label>{{ trans('storefront::product.categories') }}</label>

                    @foreach ($product->categories as $category)
                        <a href="{{ $category->url() }}">{{ $category->name }}</a>{{ $loop->last ? '' : ',' }}
                    @endforeach
                </li>
            @endif

            @if ($product->tags->isNotEmpty())
                <li>
                    <label>{{ trans('storefront::product.tags') }}</label>

                    @foreach ($product->tags as $tag)
                        <a href="{{ $tag->url() }}">{{ $tag->name }}</a>{{ $loop->last ? '' : ',' }}
                    @endforeach
                </li>
            @endif
        </ul>

        @include('storefront::public.products.show.social_share')
    </div>
    
    <div class="advance-payment-info" style="
        background: #FEF2F2; 
        border: 1px solid #FCA5A5; 
        color: #991B1B; 
        padding: 0.8rem; 
        border-radius: 0.5rem;
        font-size: 0.9rem;
        text-align: justify;
        line-height: 1.4;
        white-space: pre-line;
        ">প্রোডাক্ট ক্যাশ অন ডেলিভারিতে অর্ডার কনফার্ম করার জন্য ডেলিভারি চার্জ অগ্রিম পেমেন্ট করতে হবে। অগ্রিম টাকা পেমেন্ট করতে <a href="https://pay.peculiargadgets.com.bd/paymentlink/default">Pay Now</a> এ ক্লিক করুন। প্রোডাক্টের বাকি টাকা আপনি ক্যাশ অন ডেলিভারিতে পরিশোধ করতে পারবেন। সারা বাংলাদেশে ক্যাশ অন ডেলিভারির সুবিধা রয়েছে।
    </div>
    
    <div class="secure-checkout-info" style="margin-top: 1rem; text-align: center;">
        <img 
            src="https://peculiargadgets.com.bd/storage/media/your_payment_image_fill_name.webp" 
            alt="Secure Payment" 
            style="max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
        >
        
        <p style="color: #111827; font-size: 12px;">
            Guaranteed Safe & Secure Checkout
        </p>
    </div>
</div>
