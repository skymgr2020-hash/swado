import Foundation

/// Decodes a JSON value that is sometimes a string (Laravel `decimal:N` casts,
/// e.g. MenuItemResource.price = "250.00000") and sometimes a native number
/// (order pricing, RestaurantResource) into a single Double. Needed starting
/// Phase 2 (menu/pricing); included now so both platforms' networking layers
/// stay structurally in sync per the API contract doc.
@propertyWrapper
struct FlexibleDouble: Codable {
    var wrappedValue: Double

    init(wrappedValue: Double) {
        self.wrappedValue = wrappedValue
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        if let value = try? container.decode(Double.self) {
            wrappedValue = value
        } else if let text = try? container.decode(String.self), let value = Double(text) {
            wrappedValue = value
        } else {
            throw DecodingError.typeMismatch(
                Double.self,
                DecodingError.Context(codingPath: decoder.codingPath, debugDescription: "Expected Double or numeric String")
            )
        }
    }

    func encode(to encoder: Encoder) throws {
        var container = encoder.singleValueContainer()
        try container.encode(wrappedValue)
    }
}
