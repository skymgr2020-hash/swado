import 'package:flutter_test/flutter_test.dart';
import 'package:food_delivery_driver/models/user.dart';

void main() {
  test('theatre runners can enter the driver app and retain their role', () {
    final user = User.fromJson({'id': 1, 'role': 'theatre_runner'});
    expect(user.isDriver, isTrue);
    expect(user.isTheatreRunner, isTrue);
    expect(User.fromJson(user.toJson()).isTheatreRunner, isTrue);
  });

  test('runner membership works alongside the customer role', () {
    final user = User.fromJson({
      'id': 1, 'role': 'customer',
      'roles': [{'name': 'customer'}, {'name': 'theatre_runner'}],
    });
    expect(user.isDriver, isTrue);
    expect(user.isTheatreRunner, isTrue);
  });

  test('road drivers retain their existing dashboard', () {
    final user = User.fromJson({'id': 1, 'role': 'delivery_partner'});
    expect(user.isDriver, isTrue);
    expect(user.isTheatreRunner, isFalse);
    expect(User.fromJson({'id': 2, 'role': 'customer'}).isDriver, isFalse);
  });
}
