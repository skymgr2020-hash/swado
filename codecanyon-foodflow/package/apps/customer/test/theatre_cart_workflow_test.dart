import 'package:flutter_test/flutter_test.dart';
import 'package:food_delivery_customer/screens/customer/theatre/theatre_cart_provider.dart';
import 'package:food_delivery_customer/screens/customer/theatre/theatre_models.dart';

void main() {
  TheatreSummary venue(int id) => TheatreSummary(
    id: id, name: 'Cinema', code: 'C$id', distanceMeters: 0,
    withinGeofence: true, aisleHandoffOnly: false,
  );
  TheatreScreenSeatMap screen(int id, int theatreId) => TheatreScreenSeatMap(
    id: id, theatreId: theatreId, name: 'Screen', rows: [], sections: {},
  );
  const stall = TheatreRestaurant(id: 1, name: 'Popcorn', rating: 4, isOpen: true);

  test('changing venue clears items belonging to the previous theatre', () {
    final cart = TheatreCartProvider();
    cart.setVenue(theatre: venue(1), screen: screen(1, 1), seatLabel: 'A1', seatToken: 'old-token');
    cart.addItem(restaurant: stall, menuItemId: 1, name: 'Popcorn', price: 100);
    cart.setVenue(theatre: venue(2), screen: screen(2, 2), seatLabel: 'B1');
    expect(cart.isEmpty, isTrue);
    expect(cart.seatToken, isNull);
    cart.dispose();
  });

  test('changing a seat in the same screen preserves the cart', () {
    final cart = TheatreCartProvider();
    cart.setVenue(theatre: venue(1), screen: screen(1, 1), seatLabel: 'A1');
    cart.addItem(restaurant: stall, menuItemId: 1, name: 'Popcorn', price: 100);
    cart.setVenue(theatre: venue(1), screen: screen(1, 1), seatLabel: 'A2');
    expect(cart.totalItemCount, 1);
    expect(cart.seatLabel, 'A2');
    cart.dispose();
  });

  test('decimal restaurant ratings from SQL are accepted', () {
    expect(TheatreRestaurant.fromJson({'id': 1, 'rating': '4.50'}).rating, 4.5);
    expect(TheatreRestaurant.fromJson({'id': 1, 'rating': 4.5}).rating, 4.5);
  });
}
