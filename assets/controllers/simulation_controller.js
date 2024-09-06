import { Controller } from '@hotwired/stimulus';
import {
    create,
    forceCenter,
    forceLink,
    forceManyBody,
    forceSimulation,
    scaleOrdinal,
    schemeCategory10,
    select
} from 'd3';

export default class extends Controller {
    static values = {
        subscriptionUrl: String
    }

    networkData = {
        neurons: [
            {hash: '40cd750bba9870f18aada2478b24840a', isOrigin: true}
        ],
        connections: []
    };

    connect() {
        this.svg = this.createSimulation(this.networkData);
        this.element.appendChild(this.svg);

        const eventSource = new EventSource(this.subscriptionUrlValue.replace(/^"|"$/g, ''));
        eventSource.onmessage = event => {
            if (this.element.childElementCount) {
                const data = JSON.parse(event.data);
                this.updateData(data);
                this.networkData = data;
            }
        }
    }

    createSimulation(data) {
        // Specify the dimensions of the chart.
        const width = this.element.offsetWidth;
        const height = this.element.offsetHeight;

        // Specify the color scale.
        this.color = scaleOrdinal(schemeCategory10);

        // The force simulation mutates links and nodes, so create a copy
        // so that re-evaluating this cell produces the same result.
        this.links = data.connections.map(d => ({...d}));
        this.nodes = data.neurons.map(d => ({...d}));

        // Create a simulation with several forces.
        this.simulation = forceSimulation(this.nodes)
            .force("link", forceLink(this.links).id(d => d.hash))
            .force("charge", forceManyBody())
            .force("center", forceCenter(width / 2, height / 2))
            .on("tick", () => this.ticked());

        // Create the SVG container.
        const svg = create("svg")
            .attr("width", width)
            .attr("height", height)
            .attr("viewBox", [0, 0, width, height])
            .attr("style", "max-width: 100%; height: auto;");

        // Add a line for each link, and a circle for each node.
        this.link = svg.append("g")
            .attr("stroke", "#999")
            .attr("stroke-opacity", 0.6)
            .selectAll()
            .data(this.links)
            .join("line")
            .attr("stroke-width", d => Math.sqrt(d.ttl));

        this.node = svg.append("g")
            .attr("stroke", "#fff")
            .attr("stroke-width", 1.5)
            .selectAll()
            .data(this.nodes)
            .join("circle")
            .attr("r", 5)
            .attr("fill", d => this.color(parseInt(d.isOrigin)));

        return svg.node();
    }

    ticked() {
        this.link
            .attr("x1", d => d.source.x)
            .attr("y1", d => d.source.y)
            .attr("x2", d => d.target.x)
            .attr("y2", d => d.target.y);

        this.node
            .attr("cx", d => d.x)
            .attr("cy", d => d.y);
    }

    updateData(data) {
        // Update nodes
        this.nodes = data.neurons.map(d => ({...d}));

        // Update links
        this.links = data.connections.map(d => ({...d}));

        // Update the simulation with new nodes and links
        this.simulation.nodes(this.nodes);
        this.simulation.force("link").links(this.links);

        // Update the visual elements
        this.updateVisuals();

        // Reheat the simulation
        this.simulation.alpha(1).restart();
    }

    updateVisuals() {
        this.link = select(this.svg).select("g").selectAll("line")
            .data(this.links, d => `${d.source.hash}-${d.target.hash}`)
            .join(
                enter => enter.append("line")
                    .attr("stroke", "#999")
                    .attr("stroke-opacity", 0.6)
                    .attr("stroke-width", d => Math.sqrt(d.ttl)),
                update => update.attr("stroke-width", d => Math.sqrt(d.ttl)),
                exit => exit.remove()
            );

        this.node = select(this.svg).selectAll("g:last-of-type").selectAll("circle")
            .data(this.nodes, d => d.hash)
            .join(
                enter => enter.append("circle")
                    .attr("r", 5)
                    .attr("fill", d => this.color(parseInt(d.isOrigin)))
                    .attr("stroke", "#fff")
                    .attr("stroke-width", 1.5),
                update => update.attr("fill", d => this.color(parseInt(d.isOrigin))),
                exit => exit.remove()
            );
    }
}