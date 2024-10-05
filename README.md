# Neural Fungus Network

This Symfony project simulates an unconventional neural network by dynamically modifying the arrangement of neurons inside a simulated brain. It leverages the principles of consciousness and long and short-term memory to create a unique learning environment.

## Features

- Dynamic neuron arrangement simulation
- Consciousness and memory principles integration
- Real-time state updates using Mercure protocol
- State persistence with Redis
- Visual representation using D3.js force simulation

## Prerequisites

Before you begin, ensure you have the following installed:

- [Mercure server](https://mercure.rocks/docs/hub/install)
- [Redis server](https://redis.io/docs/latest/operate/oss_and_stack/install/install-redis/)
- PHP 7.4 or higher
- Composer

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/matkhl/NeuralFungusNetwork.git
   cd NeuralFungusNetwork
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Configure your `.env` file with the necessary environment variables (Mercure and Redis connections).

## Usage

1. Start the Symfony development server:
   ```
   symfony server:start
   ```
   or
   ```
   php bin/console server:run
   ```

2. Access the web interface at `http://localhost:8000` (default, may vary).

3. To start feeding random input to the network, run:
   ```
   php bin/console app:run
   ```

4. To reset the network's progress, use:
   ```
   php bin/console app:reset
   ```

## Visualization

The network's behavior and structure can be observed through the D3.js force simulation on the web interface. This provides a real-time visual representation of the dynamic neural network.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE.md](LICENSE) file for details.