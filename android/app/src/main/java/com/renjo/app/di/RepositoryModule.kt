package com.renjo.app.di

import com.renjo.app.data.repository.AddressRepository
import com.renjo.app.data.repository.AddressRepositoryImpl
import com.renjo.app.data.repository.AuthRepository
import com.renjo.app.data.repository.AuthRepositoryImpl
import com.renjo.app.data.repository.BrandingRepository
import com.renjo.app.data.repository.BrandingRepositoryImpl
import com.renjo.app.data.repository.MenuRepository
import com.renjo.app.data.repository.MenuRepositoryImpl
import com.renjo.app.data.repository.OrderRepository
import com.renjo.app.data.repository.OrderRepositoryImpl
import com.renjo.app.data.repository.PaymentRepository
import com.renjo.app.data.repository.PaymentRepositoryImpl
import com.renjo.app.data.repository.DiningRepository
import com.renjo.app.data.repository.DiningRepositoryImpl
import com.renjo.app.data.repository.RestaurantRepository
import com.renjo.app.data.repository.RestaurantRepositoryImpl
import com.renjo.app.data.repository.WalletRepository
import com.renjo.app.data.repository.WalletRepositoryImpl
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class RepositoryModule {

    @Binds
    @Singleton
    abstract fun bindBrandingRepository(impl: BrandingRepositoryImpl): BrandingRepository

    @Binds
    @Singleton
    abstract fun bindAuthRepository(impl: AuthRepositoryImpl): AuthRepository

    @Binds
    @Singleton
    abstract fun bindRestaurantRepository(impl: RestaurantRepositoryImpl): RestaurantRepository

    @Binds
    @Singleton
    abstract fun bindMenuRepository(impl: MenuRepositoryImpl): MenuRepository

    @Binds
    @Singleton
    abstract fun bindAddressRepository(impl: AddressRepositoryImpl): AddressRepository

    @Binds
    @Singleton
    abstract fun bindOrderRepository(impl: OrderRepositoryImpl): OrderRepository

    @Binds
    @Singleton
    abstract fun bindPaymentRepository(impl: PaymentRepositoryImpl): PaymentRepository

    @Binds
    @Singleton
    abstract fun bindWalletRepository(impl: WalletRepositoryImpl): WalletRepository

    @Binds
    @Singleton
    abstract fun bindDiningRepository(impl: DiningRepositoryImpl): DiningRepository
}
